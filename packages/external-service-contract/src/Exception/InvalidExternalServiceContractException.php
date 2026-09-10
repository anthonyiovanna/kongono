<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceContract\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceContract\Diagnostic\ExternalServiceContractDiagnostic;
use Throwable;

/** Reports one bounded failure to construct exact external-service evidence. */
#[Api(ApiStatus::Experimental)]
final class InvalidExternalServiceContractException extends ExternalServiceContractException
{
    private const array IDENTITY_VIOLATIONS = ['binding_identity_grammar', 'service_profile_identity_grammar', 'credential_source_identity_grammar', 'credential_lease_identity_grammar', 'operation_identity_grammar', 'credential_purpose_grammar'];
    private const array ENDPOINT_VIOLATIONS = ['endpoint_base_path_grammar'];
    private const array BINDING_VIOLATIONS = ['binding_shape'];
    private const array REFERENCE_VIOLATIONS = ['credential_reference_grammar'];
    private const array LEASE_VIOLATIONS = ['maximum_lifetime_milliseconds_range'];
    private const array LIFECYCLE_VIOLATIONS = ['terminal_state_required'];
    private const array RESULT_VIOLATIONS = ['failure_reason_grammar', 'success_relation', 'failure_required', 'possible_effect_requires_failed'];
    private const array LIMIT_VIOLATIONS = ['identity_bytes', 'endpoint_base_path_bytes', 'credential_reference_bytes', 'failure_reason_bytes'];

    public static function invalidIdentity(string $violation, ?Throwable $previous = null): self
    {
        return new self(ExternalServiceContractDiagnostic::InvalidIdentity, ['violation' => self::safeViolation($violation, self::IDENTITY_VIOLATIONS)], $previous);
    }

    public static function invalidEndpoint(string $violation, ?Throwable $previous = null): self
    {
        return new self(ExternalServiceContractDiagnostic::InvalidEndpoint, ['violation' => self::safeViolation($violation, self::ENDPOINT_VIOLATIONS)], $previous);
    }

    public static function invalidBinding(string $violation, ?Throwable $previous = null): self
    {
        return new self(ExternalServiceContractDiagnostic::InvalidBinding, ['violation' => self::safeViolation($violation, self::BINDING_VIOLATIONS)], $previous);
    }

    public static function invalidCredentialReference(string $violation, ?Throwable $previous = null): self
    {
        return new self(ExternalServiceContractDiagnostic::InvalidCredentialReference, ['violation' => self::safeViolation($violation, self::REFERENCE_VIOLATIONS)], $previous);
    }

    public static function invalidCredentialLease(string $violation, ?int $limit = null, ?Throwable $previous = null): self
    {
        $context = ['violation' => self::safeViolation($violation, self::LEASE_VIOLATIONS)];
        if (null !== $limit) {
            $context['limit'] = max(0, $limit);
        }

        return new self(ExternalServiceContractDiagnostic::InvalidCredentialLease, $context, $previous);
    }

    public static function invalidLifecycle(string $violation, ?string $state = null, ?Throwable $previous = null): self
    {
        $context = ['violation' => self::safeViolation($violation, self::LIFECYCLE_VIOLATIONS)];
        if (null !== $state) {
            $context['state'] = self::safeState($state);
        }

        return new self(ExternalServiceContractDiagnostic::InvalidLifecycle, $context, $previous);
    }

    public static function invalidResult(string $violation, ?string $outcome = null, ?string $state = null, ?Throwable $previous = null): self
    {
        $context = ['violation' => self::safeViolation($violation, self::RESULT_VIOLATIONS)];
        if (null !== $state) {
            $context['state'] = self::safeState($state);
        }
        if (null !== $outcome) {
            $context['outcome'] = self::safeOutcome($outcome);
        }

        return new self(ExternalServiceContractDiagnostic::InvalidResult, $context, $previous);
    }

    public static function contractLimitExceeded(string $violation, int $limit, ?int $entryCount = null, ?int $entryPosition = null, ?Throwable $previous = null): self
    {
        $context = ['violation' => self::safeViolation($violation, self::LIMIT_VIOLATIONS), 'limit' => max(0, $limit)];
        if (null !== $entryCount) {
            $context['entry_count'] = max(0, $entryCount);
        }
        if (null !== $entryPosition) {
            $context['entry_position'] = max(0, $entryPosition);
        }

        return new self(ExternalServiceContractDiagnostic::ContractLimitExceeded, $context, $previous);
    }

    /** @param list<string> $allowed */
    private static function safeViolation(string $violation, array $allowed): string
    {
        return in_array($violation, $allowed, true) ? $violation : 'unsupported_violation';
    }

    private static function safeState(string $state): string
    {
        return in_array($state, ['prepared', 'resolving', 'resolved', 'leasing', 'leased', 'invoking', 'retiring', 'retired', 'failed'], true) ? $state : 'unsupported_state';
    }

    private static function safeOutcome(string $outcome): string
    {
        return in_array($outcome, ['rejected_before_attempt', 'succeeded', 'failed_before_effect', 'cancelled_before_effect', 'cancelled_after_possible_effect', 'uncertain_effect'], true) ? $outcome : 'unsupported_outcome';
    }
}
