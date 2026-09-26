<?php

declare(strict_types=1);

namespace StorageMinimalApp;

use Kongono\StorageContract\Content\StorageChunk;
use Kongono\StorageContract\Stream\StorageByteSource;

/** One bounded caller-owned source for the example's explicit writes. */
final class ExampleByteSource implements StorageByteSource
{
    private int $position = 0;
    private bool $isClosed = false;

    public function __construct(private readonly string $bytes) {}

    public function read(int $maximumBytes): ?StorageChunk
    {
        if ($this->isClosed || $maximumBytes < 1) {
            throw new \RuntimeException('Source is unavailable.');
        }
        if ($this->exhausted()) {
            return null;
        }
        $part = substr($this->bytes, $this->position, $maximumBytes);
        $this->position += strlen($part);
        return new StorageChunk($part);
    }

    public function exhausted(): bool
    {
        return $this->position >= strlen($this->bytes);
    }

    public function close(): void
    {
        $this->isClosed = true;
    }

    public function closed(): bool
    {
        return $this->isClosed;
    }
}
