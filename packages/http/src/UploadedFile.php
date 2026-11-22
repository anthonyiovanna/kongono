<?php

declare(strict_types=1);

namespace Kongono\Http;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

/** PSR-7 uploaded file with real multipart upload support. */
final class UploadedFile implements UploadedFileInterface
{
    private bool $moved = false;
    private ?StreamInterface $stream = null;

    public function __construct(
        private readonly ?string $filePath,
        private readonly ?int $size,
        private readonly int $error,
        private readonly ?string $clientFilename = null,
        private readonly ?string $clientMediaType = null,
        ?StreamInterface $stream = null,
    ) {
        $this->stream = $stream;
    }

    public static function fromString(string $contents, string $filename, string $mediaType = 'application/octet-stream'): self
    {
        return new self(
            filePath: null,
            size: strlen($contents),
            error: UPLOAD_ERR_OK,
            clientFilename: $filename,
            clientMediaType: $mediaType,
            stream: Stream::fromString($contents),
        );
    }

    public function getStream(): StreamInterface
    {
        if ($this->moved) {
            throw new RuntimeException('Uploaded file has already been moved.');
        }
        if (UPLOAD_ERR_OK !== $this->error) {
            throw new RuntimeException(sprintf('Cannot retrieve stream due to upload error code %d.', $this->error));
        }
        if (null !== $this->stream) {
            return $this->stream;
        }
        if (null === $this->filePath || !is_file($this->filePath)) {
            throw new RuntimeException('Uploaded file path is unavailable.');
        }
        $resource = fopen($this->filePath, 'rb');
        if (false === $resource) {
            throw new RuntimeException('Unable to open uploaded file.');
        }
        $this->stream = new Stream($resource);

        return $this->stream;
    }

    public function moveTo(string $targetPath): void
    {
        if ($this->moved) {
            throw new RuntimeException('Uploaded file has already been moved.');
        }
        if (UPLOAD_ERR_OK !== $this->error) {
            throw new RuntimeException(sprintf('Cannot move file due to upload error code %d.', $this->error));
        }
        if ('' === trim($targetPath) || str_contains($targetPath, "\0")) {
            throw new RuntimeException('Invalid upload target path.');
        }
        if (is_link($targetPath)) {
            throw new RuntimeException('Refusing to overwrite symlink upload target.');
        }
        $targetDir = dirname($targetPath);
        if (is_link($targetDir) || !is_dir($targetDir)) {
            throw new RuntimeException('Upload target directory does not exist or is a symbolic link.');
        }

        if (null !== $this->filePath && is_uploaded_file($this->filePath)) {
            if (null !== $this->stream) {
                $this->stream->close();
                $this->stream = null;
            }
            if (!move_uploaded_file($this->filePath, $targetPath)) {
                throw new RuntimeException(sprintf('Failed to move uploaded file to "%s".', $targetPath));
            }
        } else {
            $stream = $this->getStream();
            if ($stream->isSeekable()) {
                $stream->rewind();
            }
            $tmp = $targetDir . DIRECTORY_SEPARATOR . '.kgn-upload-' . bin2hex(random_bytes(12)) . '.tmp';
            $dest = @fopen($tmp, 'xb');
            if (false === $dest) {
                throw new RuntimeException(sprintf('Failed to create temporary upload file in "%s".', $targetDir));
            }
            try {
                while (!$stream->eof()) {
                    $chunk = $stream->read(65536);
                    if ('' === $chunk) {
                        break;
                    }
                    $written = fwrite($dest, $chunk);
                    if (false === $written || $written !== strlen($chunk)) {
                        throw new RuntimeException(sprintf('Failed to write uploaded file to "%s".', $targetPath));
                    }
                }
                fflush($dest);
            } catch (\Throwable $e) {
                fclose($dest);
                @unlink($tmp);
                throw $e;
            }
            fclose($dest);
            @chmod($tmp, 0o600);
            if (!@rename($tmp, $targetPath)) {
                @unlink($tmp);
                throw new RuntimeException(sprintf('Failed to finalize uploaded file to "%s".', $targetPath));
            }
            $stream->close();
            $this->stream = null;
        }

        $this->moved = true;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getError(): int
    {
        return $this->error;
    }

    public function getClientFilename(): ?string
    {
        return $this->clientFilename;
    }

    public function getClientMediaType(): ?string
    {
        return $this->clientMediaType;
    }

    public function isOk(): bool
    {
        return UPLOAD_ERR_OK === $this->error;
    }
}
