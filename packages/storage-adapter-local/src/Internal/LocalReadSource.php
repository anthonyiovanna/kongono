<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterLocal\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageAdapterLocal\Exception\LocalStorageOperationException;
use Kongono\StorageContract\Content\StorageChunk;
use Kongono\StorageContract\Stream\StorageByteSource;

/** Streams one already validated locked record with explicit close ownership. */
#[Api(ApiStatus::Internal)]
final class LocalReadSource implements StorageByteSource
{
    /** @param resource $handle */
    public function __construct(private mixed $handle, private LocalObjectLock $lock, private int $remaining, private readonly int $maximumChunkBytes, private bool $closed = false) {}

    public function read(int $maximumBytes): ?StorageChunk
    {
        if ($this->closed) {
            throw LocalStorageOperationException::failed('read_after_close', 'read');
        }
        if ($maximumBytes < 1 || $maximumBytes > $this->maximumChunkBytes) {
            throw LocalStorageOperationException::failed('chunk_limit', 'read');
        }
        if (0 === $this->remaining) {
            return null;
        }
        $readLength = min($maximumBytes, $this->remaining);
        if ($readLength < 1) {
            throw LocalStorageOperationException::failed('source_read_length', 'read');
        }
        $bytes = fread($this->handle, $readLength);
        if (false === $bytes || '' === $bytes) {
            throw LocalStorageOperationException::failed('source_read_failed', 'read');
        }
        $this->remaining -= strlen($bytes);

        return new StorageChunk($bytes);
    }

    public function exhausted(): bool
    {
        return 0 === $this->remaining;
    }

    public function close(): void
    {
        if ($this->closed) {
            return;
        }
        $this->closed = true;
        $failed = !@fclose($this->handle);
        try {
            $this->lock->close();
        } catch (LocalStorageOperationException) {
            $failed = true;
        }
        if ($failed) {
            throw LocalStorageOperationException::cleanup('read');
        }
    }

    public function closed(): bool
    {
        return $this->closed;
    }
}
