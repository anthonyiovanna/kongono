<?php

declare(strict_types=1);

namespace Kongono\StorageApplication\Tests\Fixture;

use Kongono\StorageContract\Content\StorageChunk;
use Kongono\StorageContract\Stream\StorageByteSource;

final class CountingByteSource implements StorageByteSource
{
    public int $reads = 0;
    public int $closes = 0;
    private int $position = 0;
    private bool $isClosed = false;

    public function __construct(private readonly string $bytes) {}

    public function read(int $maximumBytes): ?StorageChunk
    {
        ++$this->reads;
        if ($this->position >= strlen($this->bytes)) {
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
        ++$this->closes;
        $this->isClosed = true;
    }

    public function closed(): bool
    {
        return $this->isClosed;
    }
}
