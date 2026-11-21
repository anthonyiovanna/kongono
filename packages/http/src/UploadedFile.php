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

        if (null !== $this->filePath && is_uploaded_file($this->filePath)) {
            if (!move_uploaded_file($this->filePath, $targetPath)) {
                throw new RuntimeException(sprintf('Failed to move uploaded file to "%s".', $targetPath));
            }
        } else {
            $stream = $this->getStream();
            if ($stream->isSeekable()) {
                $stream->rewind();
            }
            if (false === file_put_contents($targetPath, $stream->getContents(), LOCK_EX)) {
                throw new RuntimeException(sprintf('Failed to write uploaded file to "%s".', $targetPath));
            }
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
