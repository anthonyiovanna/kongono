<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressRuntime\Internal;

use Error;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressRuntime\Exception\EgressCleanupException;
use Kongono\NetworkEgressRuntime\Exception\EgressOperationAndCleanupException;
use Kongono\NetworkEgressRuntime\Execution\EgressRuntimeResult;
use Throwable;

/** Applies the exact primary and close-failure matrix after session retirement. */
#[Api(ApiStatus::Internal)]
final class LifecycleFailureComposer
{
    /** @param list<Throwable> $cleanupFailures */
    public static function throwIfNeeded(?Throwable $primary, array $cleanupFailures, EgressRuntimeResult $cleanupResult): void
    {
        if ($primary instanceof Error) {
            throw $primary;
        }
        if (null === $primary && [] === $cleanupFailures) {
            return;
        }
        if (null !== $primary && [] === $cleanupFailures) {
            throw $primary;
        }
        if (null === $primary) {
            throw new EgressCleanupException($cleanupResult, $cleanupFailures);
        }
        throw new EgressOperationAndCleanupException($cleanupResult, $primary, $cleanupFailures);
    }

    private function __construct() {}
}
