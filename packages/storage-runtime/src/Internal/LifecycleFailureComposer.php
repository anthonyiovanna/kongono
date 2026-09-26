<?php

declare(strict_types=1);

namespace Kongono\StorageRuntime\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageRuntime\Exception\StorageRuntimeCleanupException;
use Kongono\StorageRuntime\Exception\StorageRuntimeOperationAndCleanupException;
use Throwable;

/** Preserves primary and cleanup failures as distinct causal evidence. */
#[Api(ApiStatus::Internal)]
final class LifecycleFailureComposer
{
    /** @param list<Throwable> $cleanupFailures */
    public static function finish(string $operation, ?Throwable $primary, array $cleanupFailures): void
    {
        if (null !== $primary && [] !== $cleanupFailures) {
            throw new StorageRuntimeOperationAndCleanupException($operation, $primary, $cleanupFailures);
        }
        if (null !== $primary) {
            throw $primary;
        }
        if ([] !== $cleanupFailures) {
            throw new StorageRuntimeCleanupException($operation, $cleanupFailures);
        }
    }
}
