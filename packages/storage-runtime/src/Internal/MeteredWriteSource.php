<?php

declare(strict_types=1);

namespace Kongono\StorageRuntime\Internal;

use Error;
use HashContext;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageContract\Content\StorageChunk;
use Kongono\StorageContract\Digest\StorageContentDigest;
use Kongono\StorageContract\Stream\StorageByteSource;
use Kongono\StorageRuntime\Exception\InvalidStorageRuntimeException;
use Throwable;

/** Meters one transferred caller-owned write source without buffering it. */
#[Api(ApiStatus::Internal)]
final class MeteredWriteSource implements StorageByteSource
{
    private int $observedBytes = 0;
    private bool $observedEnd = false;
    private bool $closed = false;
    private HashContext $hash;

    public function __construct(
        private readonly StorageByteSource $source,
        private readonly string $operation,
        private readonly int $declaredBytes,
        private readonly int $maximumChunkBytes,
        private readonly StorageContentDigest $expectedDigest,
    ) {
        $this->hash = hash_init($expectedDigest->algorithm()->value);
    }

    public function read(int $maximumBytes): ?StorageChunk
    {
        if ($this->closed || $maximumBytes < 1 || $maximumBytes > $this->maximumChunkBytes) {
            throw InvalidStorageRuntimeException::operation('source_read_bound', $this->operation, context: ['limit' => $this->maximumChunkBytes]);
        }
        if ($this->observedEnd) {
            throw InvalidStorageRuntimeException::operation('source_after_exhaustion', $this->operation);
        }
        $remaining = $this->declaredBytes - $this->observedBytes;
        try {
            $chunk = $this->source->read(max(1, min($maximumBytes, max(1, $remaining))));
        } catch (Error $failure) {
            throw $failure;
        } catch (Throwable $failure) {
            throw InvalidStorageRuntimeException::operation('source_failure', $this->operation, $failure);
        }
        if (null === $chunk) {
            if (!$this->source->exhausted()) {
                throw InvalidStorageRuntimeException::operation('contradictory_exhaustion', $this->operation);
            }
            $this->observedEnd = true;

            return null;
        }
        $count = $chunk->byteCount()->value();
        if ($count > $maximumBytes || $count > $this->maximumChunkBytes || $this->observedBytes + $count > $this->declaredBytes) {
            throw InvalidStorageRuntimeException::operation('source_byte_limit', $this->operation, context: ['byte_count' => $this->observedBytes + $count, 'limit' => $this->declaredBytes]);
        }
        hash_update($this->hash, $chunk->bytes());
        $this->observedBytes += $count;

        return $chunk;
    }

    public function exhausted(): bool
    {
        return $this->observedEnd;
    }

    public function close(): void
    {
        if ($this->closed) {
            return;
        }
        $this->closed = true;
        if (!$this->source->closed()) {
            $this->source->close();
        }
    }

    public function closed(): bool
    {
        return $this->closed;
    }

    public function validateCompleted(): void
    {
        if (!$this->observedEnd || $this->observedBytes !== $this->declaredBytes) {
            throw InvalidStorageRuntimeException::evidence('write_consumption_mismatch', $this->operation, 'completed', context: ['byte_count' => $this->observedBytes, 'limit' => $this->declaredBytes]);
        }
        $actual = hash_final($this->hash, false);
        if (!hash_equals($this->expectedDigest->digest(), $actual)) {
            throw InvalidStorageRuntimeException::evidence('write_digest_mismatch', $this->operation, 'completed');
        }
    }
}
