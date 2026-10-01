<?php

declare(strict_types=1);

namespace Kongono\StorageRuntime\Tests\Fixture;

use Kongono\StorageContract\Content\StorageChunk;
use Kongono\StorageContract\Stream\StorageByteSource;
use RuntimeException;

final class ScriptedStorageByteSource implements StorageByteSource
{
    private int $offset = 0;
    private bool $closed = false;
    public int $closeCalls = 0;
    public bool $failClosedObservation = false;

    public function __construct(
        private readonly string $bytes,
        private readonly bool $failRead = false,
        private readonly bool $failClose = false,
        private readonly bool $contradictNull = false,
    ) {}

    public function read(int $maximumBytes): ?StorageChunk
    {
        if ($this->closed) {
            throw new RuntimeException('fixture-read-after-close');
        }
        if ($this->failRead) {
            throw new RuntimeException('fixture-read-failure');
        }
        if ($this->offset >= strlen($this->bytes)) {
            return null;
        }
        $bytes = substr($this->bytes, $this->offset, $maximumBytes);
        $this->offset += strlen($bytes);

        return new StorageChunk($bytes);
    }

    public function exhausted(): bool
    {
        return !$this->contradictNull && $this->offset >= strlen($this->bytes);
    }

    public function close(): void
    {
        ++$this->closeCalls;
        $this->closed = true;
        if ($this->failClose) {
            throw new RuntimeException('fixture-close-failure');
        }
    }

    public function closed(): bool
    {
        if ($this->failClosedObservation) {
            throw new RuntimeException('fixture-closed-observation-failure');
        }
        return $this->closed;
    }
}
