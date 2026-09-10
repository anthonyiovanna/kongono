<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceCredentialRuntime\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceCredentialRuntime\Exception\CredentialLeaseCleanupException;
use Kongono\ExternalServiceCredentialRuntime\Exception\CredentialLeaseOperationAndCleanupException;
use Kongono\ExternalServiceCredentialRuntime\Exception\CredentialLeaseOperationException;
use Kongono\ExternalServiceCredentialRuntime\Exception\InvalidCredentialLeaseRuntimeException;
use Kongono\ExternalServiceCredentialRuntime\Execution\CredentialLeaseRuntimeResult;
use Throwable;

/** Applies the exact primary-and-retirement-failure matrix. */
#[Api(ApiStatus::Internal)]
final class LifecycleFailureComposer
{
    public static function throwIfNeeded(
        ?Throwable $primaryFailure,
        ?CredentialLeaseRuntimeResult $primaryResult,
        string $primaryStage,
        int $limit,
        int $observedCount,
        ?Throwable $cleanupFailure,
        ?CredentialLeaseRuntimeResult $cleanupResult,
    ): void {
        if (null === $primaryFailure && null === $cleanupFailure) {
            return;
        }
        if (null !== $primaryFailure && null === $cleanupFailure && null !== $primaryResult) {
            throw new CredentialLeaseOperationException($primaryResult, $primaryFailure, $primaryStage, $limit, $observedCount);
        }
        if (null === $primaryFailure && null !== $cleanupResult) {
            throw new CredentialLeaseCleanupException($cleanupResult, [$cleanupFailure]);
        }
        if (null !== $primaryFailure && null !== $cleanupFailure && null !== $cleanupResult) {
            throw new CredentialLeaseOperationAndCleanupException($cleanupResult, $primaryFailure, [$cleanupFailure]);
        }
        throw InvalidCredentialLeaseRuntimeException::invalid('exception_result_relation');
    }

    private function __construct() {}
}
