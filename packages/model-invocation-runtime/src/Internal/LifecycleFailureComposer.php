<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationRuntime\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\Receipt\InvocationOutcome;
use Kongono\ModelInvocationRuntime\Exception\InvocationCleanupException;
use Kongono\ModelInvocationRuntime\Exception\InvocationOperationAndCleanupException;
use Kongono\ModelInvocationRuntime\Execution\InvocationRuntimeRequest;
use Throwable;

/** Applies the exact primary-and-close-failure matrix after session retirement. */
#[Api(ApiStatus::Internal)]
final class LifecycleFailureComposer
{
    /** @param list<Throwable> $cleanupFailures */
    public static function throwIfNeeded(InvocationRuntimeRequest $request, ?Throwable $primaryFailure, array $cleanupFailures): void
    {
        if (null === $primaryFailure && [] === $cleanupFailures) {
            return;
        }
        if (null !== $primaryFailure && [] === $cleanupFailures) {
            throw $primaryFailure;
        }
        $receipt = ReceiptFactory::create($request, InvocationOutcome::Uncertain, 1, null, false);
        if (null === $primaryFailure) {
            throw new InvocationCleanupException($receipt, $cleanupFailures);
        }
        throw new InvocationOperationAndCleanupException($receipt, $primaryFailure, $cleanupFailures);
    }

    private function __construct() {}
}
