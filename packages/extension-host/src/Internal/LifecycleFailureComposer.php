<?php

declare(strict_types=1);

namespace Kongono\ExtensionHost\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionHost\Exception\ExtensionHostLifecycleException;
use Throwable;

/** Applies the exact primary-and-ordered-cleanup failure matrix for hosted extension lifecycles. */
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
        if (null === $primaryFailure && 1 === count($cleanupFailures)) {
            throw $cleanupFailures[0];
        }

        throw new ExtensionHostLifecycleException($primaryFailure, $cleanupFailures);
    }

    private function __construct() {}
}
