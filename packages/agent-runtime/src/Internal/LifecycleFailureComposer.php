<?php

declare(strict_types=1);

namespace Kongono\AgentRuntime\Internal;

use Kongono\AgentRuntime\Exception\AgentRuntimeCleanupException;
use Kongono\AgentRuntime\Exception\AgentRuntimeOperationAndCleanupException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Applies the exact primary-and-cleanup failure matrix after all close attempts. */
#[Api(ApiStatus::Internal)]
final class LifecycleFailureComposer
{
    /** @param list<Throwable> $cleanupFailures */
    public static function throwIfNeeded(?Throwable $primaryFailure, array $cleanupFailures): void
    {
        if (null === $primaryFailure && [] === $cleanupFailures) {
            return;
        }
        if (null !== $primaryFailure && [] === $cleanupFailures) {
            throw $primaryFailure;
        }
        if (null === $primaryFailure) {
            throw new AgentRuntimeCleanupException($cleanupFailures);
        }
        throw new AgentRuntimeOperationAndCleanupException($primaryFailure, $cleanupFailures);
    }

    private function __construct() {}
}
