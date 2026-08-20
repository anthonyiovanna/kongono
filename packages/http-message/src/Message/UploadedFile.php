<?php

declare(strict_types=1);

namespace Kongono\Http\Message\Message;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Http\Message\Diagnostic\HttpMessageDiagnostic;
use Kongono\Http\Message\Exception\HttpMessageRuntimeException;
use Kongono\Http\Message\Exception\InvalidMessageArgumentException;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

/** Models one uploaded stream with one-way explicit local movement. */
#[Api(ApiStatus::Experimental)]
final class UploadedFile implements UploadedFileInterface
{
    private bool $moved = false;

    public function __construct(
        private readonly StreamInterface $stream,
        private readonly ?int $size = null,
        private readonly int $error = UPLOAD_ERR_OK,
        private readonly ?string $clientFilename = null,
        private readonly ?string $clientMediaType = null,
    ) {
        if (!in_array($error, [UPLOAD_ERR_OK, UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE, UPLOAD_ERR_PARTIAL, UPLOAD_ERR_NO_FILE, UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION], true)) {
            throw new InvalidMessageArgumentException(HttpMessageDiagnostic::InvalidUploadError, ['upload_error' => $error]);
        }
        if (null !== $size && $size < 0) {
            throw new InvalidMessageArgumentException(HttpMessageDiagnostic::InvalidUploadSize, ['upload_size' => $size]);
        }
    }

    public function getStream(): StreamInterface
    {
        if ($this->moved) {
            throw new HttpMessageRuntimeException(HttpMessageDiagnostic::UploadedStreamUnavailable, ['state' => 'moved']);
        }
        if (UPLOAD_ERR_OK !== $this->error) {
            throw new HttpMessageRuntimeException(HttpMessageDiagnostic::UploadedStreamUnavailable, ['state' => 'upload_error']);
        }
        return $this->stream;
    }

    public function moveTo(string $targetPath): void
    {
        if ('' === $targetPath || str_contains($targetPath, "\0")) {
            throw new InvalidMessageArgumentException(HttpMessageDiagnostic::InvalidUploadTarget, ['target_length' => strlen($targetPath), 'violation' => '' === $targetPath ? 'empty' : 'nul']);
        }
        if ($this->moved) {
            throw new HttpMessageRuntimeException(HttpMessageDiagnostic::UploadedFileAlreadyMoved);
        }
        if (UPLOAD_ERR_OK !== $this->error) {
            throw new HttpMessageRuntimeException(HttpMessageDiagnostic::UploadedStreamUnavailable, ['state' => 'upload_error']);
        }

        $target = @fopen($targetPath, 'wb');
        if (false === $target) {
            throw new HttpMessageRuntimeException(HttpMessageDiagnostic::UploadedFileMoveFailed, ['operation' => 'open_target']);
        }
        $completed = false;
        try {
            if ($this->stream->isSeekable()) {
                $this->stream->rewind();
            }
            while (true) {
                $eof = $this->stream->eof();
                if ($eof) {
                    break;
                }
                $chunk = $this->stream->read(8192);
                $eofAfterRead = $this->stream->eof();
                if ('' === $chunk && !$eofAfterRead) {
                    throw new HttpMessageRuntimeException(HttpMessageDiagnostic::UploadedFileMoveFailed, ['operation' => 'read_source']);
                }
                $offset = 0;
                while ($offset < strlen($chunk)) {
                    $written = @fwrite($target, substr($chunk, $offset));
                    if (false === $written || 0 === $written) {
                        throw new HttpMessageRuntimeException(HttpMessageDiagnostic::UploadedFileMoveFailed, ['operation' => 'copy_target']);
                    }
                    $offset += $written;
                }
            }
            $completed = true;
        } finally {
            $closed = @fclose($target);
            if (!$closed && $completed) {
                throw new HttpMessageRuntimeException(HttpMessageDiagnostic::UploadedFileMoveFailed, ['operation' => 'close_target']);
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
}
