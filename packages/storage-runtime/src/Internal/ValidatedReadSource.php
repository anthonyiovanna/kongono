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
use Kongono\StorageRuntime\Exception\StorageRuntimeOperationException;
use Throwable;

/** Validates one adapter read stream and owns its explicit retirement. */
#[Api(ApiStatus::Internal)]
final class ValidatedReadSource implements StorageByteSource
{
    private int $observedBytes = 0;
    private bool $exhausted = false;
    private bool $closed = false;
    private ?HashContext $hash;

    public function __construct(
        private readonly StorageByteSource $source,
        private readonly OperationScope $scope,
        private readonly int $expectedBytes,
        private readonly int $maximumChunkBytes,
        private readonly ?StorageContentDigest $expectedDigest,
    ) {
        $this->hash = null === $expectedDigest ? null : hash_init($expectedDigest->algorithm()->value);
    }

    public function read(int $maximumBytes): ?StorageChunk
    {
        if ($this->closed || $maximumBytes < 1 || $maximumBytes > $this->maximumChunkBytes) {
            throw InvalidStorageRuntimeException::operation('source_read_bound', 'read', context: ['limit' => $this->maximumChunkBytes]);
        }
        try {
            $chunk = $this->source->read($maximumBytes);
            if (null === $chunk) {
                if (!$this->source->exhausted()) {
                    throw InvalidStorageRuntimeException::evidence('contradictory_exhaustion', 'read', 'completed');
                }
                if ($this->observedBytes !== $this->expectedBytes) {
                    throw InvalidStorageRuntimeException::evidence('read_byte_count_mismatch', 'read', 'completed', context: ['byte_count' => $this->observedBytes, 'limit' => $this->expectedBytes]);
                }
                if (null !== $this->expectedDigest) {
                    $hash = $this->hash;
                    if (null === $hash || !hash_equals($this->expectedDigest->digest(), hash_final($hash, false))) {
                        throw InvalidStorageRuntimeException::evidence('read_digest_mismatch', 'read', 'completed');
                    }
                }
                $this->exhausted = true;
                $this->retire();

                return null;
            }
            $count = $chunk->byteCount()->value();
            if ($count > $maximumBytes || $count > $this->maximumChunkBytes || $this->observedBytes + $count > $this->expectedBytes) {
                throw InvalidStorageRuntimeException::evidence('read_chunk_limit', 'read', 'completed', context: ['byte_count' => $this->observedBytes + $count, 'limit' => $this->expectedBytes]);
            }
            if (null !== $this->hash) {
                hash_update($this->hash, $chunk->bytes());
            }
            $this->observedBytes += $count;

            return $chunk;
        } catch (Error $failure) {
            $this->retire($failure);
            throw $failure;
        } catch (InvalidStorageRuntimeException $failure) {
            $this->retire($failure);
            throw $failure;
        } catch (Throwable $failure) {
            $operationFailure = StorageRuntimeOperationException::adapterFailed('read', $failure);
            $this->retire($operationFailure);
            throw $operationFailure;
        }
    }

    public function exhausted(): bool
    {
        return $this->exhausted;
    }

    public function close(): void
    {
        if (!$this->closed) {
            $this->retire();
        }
    }

    public function closed(): bool
    {
        return $this->closed;
    }

    private function retire(?Throwable $primary = null): void
    {
        $cleanup = [];
        if (!$this->closed) {
            $this->closed = true;
            try {
                if (!$this->source->closed()) {
                    $this->source->close();
                }
            } catch (Throwable $failure) {
                $cleanup[] = $failure;
            }
            try {
                if (!$this->scope->retired()) {
                    $this->scope->retire();
                }
            } catch (Throwable $failure) {
                $cleanup[] = $failure;
            }
        }
        LifecycleFailureComposer::finish('read', $primary, $cleanup);
    }
}
