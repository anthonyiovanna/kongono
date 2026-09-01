<?php

declare(strict_types=1);

namespace Kongono\AgentApplication\Internal;

use Kongono\AgentApplication\Exception\AgentApplicationCleanupException;
use Kongono\AgentApplication\Exception\AgentApplicationOperationAndCleanupException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Applies the pre-runtime primary-and-cleanup failure matrix after every close attempt. */
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
            throw new AgentApplicationCleanupException($cleanupFailures);
        }
        throw new AgentApplicationOperationAndCleanupException($primaryFailure, $cleanupFailures);
    }

    private function __construct() {}
}
