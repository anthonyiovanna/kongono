<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceCredentialRuntime\Failure;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceCredentialRuntime\Exception\InvalidCredentialLeaseRuntimeException;

/** Carries one closed failure kind and reason without foreign or credential values. */
#[Api(ApiStatus::Experimental)]
final readonly class CredentialLeaseRuntimeFailure
{
    /** @var array<string, list<string>> */
    private const array REASONS = [
        'clock' => ['clock_failed', 'clock_negative', 'clock_regression'],
        'source' => ['source_failed'],
        'lease_protocol' => ['lease_reference_mismatch', 'lease_purpose_mismatch', 'lease_state_invalid'],
        'lifetime' => ['lifetime_exceeded'],
        'consumer' => ['consumer_failed'],
        'retirement' => ['retirement_failed', 'retirement_state_invalid'],
    ];

    public function __construct(private CredentialLeaseRuntimeFailureKind $kind, private string $reason)
    {
        if (!in_array($reason, self::REASONS[$kind->value], true)) {
            throw InvalidCredentialLeaseRuntimeException::invalid('failure_reason_relation');
        }
    }

    public function kind(): CredentialLeaseRuntimeFailureKind
    {
        return $this->kind;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function equals(self $other): bool
    {
        return $this->kind === $other->kind && $this->reason === $other->reason;
    }
}
