<?php

declare(strict_types=1);

namespace Kongono\Http;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

/** Clean PSR-7 stream supporting strings, php://temp resources, and chunked reads. */
final class Stream implements StreamInterface
{
    /** @var resource|null */
    private mixed $resource;

    private bool $seekable = false;
    private bool $readable = false;
    private bool $writable = false;

    /** @param resource $resource */
    public function __construct(mixed $resource)
    {
        if (!is_resource($resource)) {
            throw new InvalidArgumentException('Stream requires a valid PHP stream resource.');
        }

        $this->resource = $resource;
        $meta = stream_get_meta_data($resource);
        $this->seekable = $meta['seekable'];
        $mode = $meta['mode'];
        $this->readable = str_contains($mode, 'r') || str_contains($mode, '+');
        $this->writable = str_contains($mode, 'w') || str_contains($mode, 'a') || str_contains($mode, 'x') || str_contains($mode, 'c') || str_contains($mode, '+');
    }

    public static function fromString(string $content = ''): self
    {
        $resource = fopen('php://temp', 'r+b');
        if (false === $resource) {
            throw new RuntimeException('Unable to open php://temp stream.');
        }
        if ('' !== $content) {
            fwrite($resource, $content);
            rewind($resource);
        }

        return new self($resource);
    }

    public function __toString(): string
    {
        if (null === $this->resource) {
            return '';
        }

        try {
            if ($this->seekable) {
                $this->rewind();
            }

            return $this->getContents();
        } catch (RuntimeException) {
            return '';
        }
    }

    public function close(): void
    {
        if (is_resource($this->resource)) {
            fclose($this->resource);
        }
        $this->detach();
    }

    public function detach()
    {
        $resource = $this->resource;
        $this->resource = null;
        $this->seekable = false;
        $this->readable = false;
        $this->writable = false;

        return $resource;
    }

    public function getSize(): ?int
    {
        if (!is_resource($this->resource)) {
            return null;
        }
        $stats = fstat($this->resource);

        return is_array($stats) ? $stats['size'] : null;
    }

    public function tell(): int
    {
        if (!is_resource($this->resource)) {
            throw new RuntimeException('Stream is detached.');
        }
        $position = ftell($this->resource);
        if (false === $position) {
            throw new RuntimeException('Unable to determine stream position.');
        }

        return $position;
    }

    public function eof(): bool
    {
        return !is_resource($this->resource) || feof($this->resource);
    }

    public function isSeekable(): bool
    {
        return $this->seekable;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if (!is_resource($this->resource) || !$this->seekable) {
            throw new RuntimeException('Stream is not seekable.');
        }
        if (0 !== fseek($this->resource, $offset, $whence)) {
            throw new RuntimeException('Unable to seek stream.');
        }
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function isWritable(): bool
    {
        return $this->writable;
    }

    public function write(string $string): int
    {
        if (!is_resource($this->resource) || !$this->writable) {
            throw new RuntimeException('Stream is not writable.');
        }
        $written = fwrite($this->resource, $string);
        if (false === $written) {
            throw new RuntimeException('Unable to write to stream.');
        }

        return $written;
    }

    public function isReadable(): bool
    {
        return $this->readable;
    }

    public function read(int $length): string
    {
        if (!is_resource($this->resource) || !$this->readable) {
            throw new RuntimeException('Stream is not readable.');
        }
        if ($length < 0) {
            throw new InvalidArgumentException('Read length must be non-negative.');
        }
        if (0 === $length) {
            return '';
        }
        $data = fread($this->resource, $length);
        if (false === $data) {
            throw new RuntimeException('Unable to read from stream.');
        }

        return $data;
    }

    public function getContents(): string
    {
        if (!is_resource($this->resource) || !$this->readable) {
            throw new RuntimeException('Stream is not readable.');
        }
        $contents = stream_get_contents($this->resource);
        if (false === $contents) {
            throw new RuntimeException('Unable to read stream contents.');
        }

        return $contents;
    }

    public function getMetadata(?string $key = null): mixed
    {
        if (!is_resource($this->resource)) {
            return null === $key ? [] : null;
        }
        $meta = stream_get_meta_data($this->resource);
        if (null === $key) {
            return $meta;
        }

        return $meta[$key] ?? null;
    }
}
