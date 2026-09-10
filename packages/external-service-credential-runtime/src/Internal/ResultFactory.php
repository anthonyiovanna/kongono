<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceCredentialRuntime\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceCredentialRuntime\Execution\CredentialLeaseRuntimeResult;
use Kongono\ExternalServiceCredentialRuntime\Failure\CredentialLeaseRuntimeFailure;
use Kongono\ExternalServiceCredentialRuntime\Outcome\CredentialLeaseRuntimeOutcome;

/** Builds detached terminal results from one operation-local lifecycle. */
#[Api(ApiStatus::Internal)]
final class ResultFactory
{
    public static function create(
        LeaseLifecycle $lifecycle,
        CredentialLeaseRuntimeOutcome $outcome,
        ?CredentialLeaseRuntimeFailure $failure,
        int $elapsedMilliseconds,
        bool $cleanupComplete,
    ): CredentialLeaseRuntimeResult {
        return new CredentialLeaseRuntimeResult(
            $outcome,
            $lifecycle->state(),
            $failure,
            $elapsedMilliseconds,
            $lifecycle->acquisitionAttemptCount(),
            $lifecycle->materialAccessAttemptCount(),
            $lifecycle->retirementAttemptCount(),
            $cleanupComplete,
        );
    }

    private function __construct() {}
}
