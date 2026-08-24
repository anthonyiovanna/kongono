<?php

declare(strict_types=1);

namespace Kongono\Composition\Runtime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Composition\Exception\CompositionLifecycleException;
use Throwable;

/** Applies the accepted primary-and-cleanup failure precedence for generated boundaries. */
#[Api(ApiStatus::Internal)]
final class LifecycleFailureComposer
{
    /** @param list<Throwable> $cleanupFailures */
    public static function throwIfNeeded(string $boundary, ?Throwable $primaryFailure, array $cleanupFailures): void
    {
        if (null === $primaryFailure && [] === $cleanupFailures) {
            return;
        }
        if (null !== $primaryFailure && [] === $cleanupFailures) {
            throw $primaryFailure;
        }
        if (null === $primaryFailure && 1 === count($cleanupFailures)) {
            throw $cleanupFailures[0];
        }

        throw new CompositionLifecycleException($primaryFailure ?? $cleanupFailures[0], $cleanupFailures, $boundary);
    }

    private function __construct() {}
}
