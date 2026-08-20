<?php

declare(strict_types=1);

namespace Kongono\Http\Message\Message;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Http\Message\Diagnostic\HttpMessageDiagnostic;
use Kongono\Http\Message\Exception\HttpMessageRuntimeException;
use Kongono\Http\Message\Exception\InvalidMessageArgumentException;
use Kongono\Http\Message\Internal\HttpMessageValidation;
use Psr\Http\Message\StreamInterface;

/** Wraps one caller-visible PHP stream resource without ambient ownership. */
#[Api(ApiStatus::Experimental)]
final class Stream implements StreamInterface
{
    /** @var resource|null */
    private mixed $resource;
    private bool $readable;
    private bool $writable;
    private bool $seekable;

    /** @param resource $resource */
    public function __construct(mixed $resource)
    {
        if (!is_resource($resource) || 'stream' !== get_resource_type($resource)) {
            throw new InvalidMessageArgumentException(HttpMessageDiagnostic::InvalidStreamResource, ['actual_value_kind' => HttpMessageValidation::valueKind($resource)]);
        }
        $metadata = @stream_get_meta_data($resource);
        $mode = $metadata['mode'];
        $this->resource = $resource;
        $this->readable = self::modeIsReadable($mode);
        $this->writable = self::modeIsWritable($mode);
        $this->seekable = $metadata['seekable'];
    }

    public function __toString(): string
    {
        try {
            if (!$this->isReadable() || !$this->isSeekable()) {
                return '';
            }
            $this->rewind();
            return $this->getContents();
        } catch (\Throwable) {
            return '';
        }
    }

    public function close(): void
    {
        if (null === $this->resource) {
            return;
        }
        $resource = $this->resource;
        $this->resource = null;
        @fclose($resource);
    }

    public function detach()
    {
        $resource = $this->resource;
        $this->resource = null;
        return $resource;
    }

    public function getSize(): ?int
    {
        if (null === $this->resource) {
            return null;
        }
        $stat = @fstat($this->resource);
        return false === $stat ? null : $stat['size'];
    }

    public function tell(): int
    {
        $resource = $this->attached('tell');
        $position = @ftell($resource);
        if (false === $position) {
            throw $this->operationFailed('tell');
        }
        return $position;
    }

    public function eof(): bool
    {
        return null === $this->resource || feof($this->resource);
    }

    public function isSeekable(): bool
    {
        return null !== $this->resource && $this->seekable;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        $resource = $this->attached('seek');
        if (!$this->seekable) {
            throw new HttpMessageRuntimeException(HttpMessageDiagnostic::StreamNotSeekable, ['operation' => 'seek']);
        }
        if (0 !== @fseek($resource, $offset, $whence)) {
            throw $this->operationFailed('seek');
        }
    }

    public function rewind(): void
    {
        $this->seek(0);
    }
    public function isWritable(): bool
    {
        return null !== $this->resource && $this->writable;
    }

    public function write(string $string): int
    {
        $resource = $this->attached('write');
        if (!$this->writable) {
            throw new HttpMessageRuntimeException(HttpMessageDiagnostic::StreamNotWritable, ['operation' => 'write']);
        }
        $written = @fwrite($resource, $string);
        if (false === $written) {
            throw $this->operationFailed('write');
        }
        return $written;
    }

    public function isReadable(): bool
    {
        return null !== $this->resource && $this->readable;
    }

    public function read(int $length): string
    {
        if ($length < 0) {
            throw new InvalidMessageArgumentException(HttpMessageDiagnostic::InvalidStreamReadLength, ['requested_length' => $length]);
        }
        $resource = $this->attached('read');
        if (!$this->readable) {
            throw new HttpMessageRuntimeException(HttpMessageDiagnostic::StreamNotReadable, ['operation' => 'read']);
        }
        if (0 === $length) {
            return '';
        }
        $contents = @fread($resource, $length);
        if (false === $contents) {
            throw $this->operationFailed('read');
        }
        return $contents;
    }

    public function getContents(): string
    {
        $resource = $this->attached('get_contents');
        if (!$this->readable) {
            throw new HttpMessageRuntimeException(HttpMessageDiagnostic::StreamNotReadable, ['operation' => 'get_contents']);
        }
        $contents = @stream_get_contents($resource);
        if (false === $contents) {
            throw $this->operationFailed('get_contents');
        }
        return $contents;
    }

    public function getMetadata(?string $key = null)
    {
        if (null === $this->resource) {
            return null === $key ? [] : null;
        }
        $metadata = @stream_get_meta_data($this->resource);
        return null === $key ? $metadata : ($metadata[$key] ?? null);
    }

    /** @return resource */
    private function attached(string $operation): mixed
    {
        if (null === $this->resource) {
            throw new HttpMessageRuntimeException(HttpMessageDiagnostic::DetachedStream, ['operation' => $operation]);
        }
        return $this->resource;
    }

    private function operationFailed(string $operation): HttpMessageRuntimeException
    {
        return new HttpMessageRuntimeException(HttpMessageDiagnostic::StreamOperationFailed, ['operation' => $operation]);
    }

    private static function modeIsReadable(string $mode): bool
    {
        return str_contains($mode, '+') || str_starts_with($mode, 'r');
    }

    private static function modeIsWritable(string $mode): bool
    {
        return str_contains($mode, '+') || 1 === preg_match('/^[waxc]/D', $mode);
    }
}
