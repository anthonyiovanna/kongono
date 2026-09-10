<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceCredentialRuntime\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceContract\Credential\CredentialLease;
use Kongono\ExternalServiceContract\Credential\CredentialLeaseRequest;
use Kongono\ExternalServiceContract\Credential\CredentialLeaseState;
use Kongono\ExternalServiceCredentialRuntime\Exception\InvalidCredentialLeaseRuntimeException;

/** Validates exact lease relations without reading credential material. */
#[Api(ApiStatus::Internal)]
final class CredentialLeaseGuard
{
    public static function validateInitial(CredentialLeaseRequest $request, CredentialLease $lease): void
    {
        $lease->identity();
        if (!$lease->reference()->equals($request->reference())) {
            throw InvalidCredentialLeaseRuntimeException::invalid('lease_reference_mismatch');
        }
        if (!$lease->purpose()->equals($request->purpose())) {
            throw InvalidCredentialLeaseRuntimeException::invalid('lease_purpose_mismatch');
        }
        if (CredentialLeaseState::Acquired !== $lease->state()) {
            throw InvalidCredentialLeaseRuntimeException::invalid('lease_state_invalid');
        }
    }

    public static function validateAfterMaterial(CredentialLease $lease): void
    {
        if (CredentialLeaseState::Acquired !== $lease->state()) {
            throw InvalidCredentialLeaseRuntimeException::invalid('lease_state_invalid');
        }
    }

    public static function validateRetired(CredentialLease $lease): void
    {
        if (CredentialLeaseState::Retired !== $lease->state()) {
            throw InvalidCredentialLeaseRuntimeException::invalid('lease_state_invalid');
        }
    }

    private function __construct() {}
}
