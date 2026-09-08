<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressContract\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressContract\Diagnostic\NetworkEgressContractDiagnostic;
use Throwable;

/** Reports one bounded failure to construct an exact egress contract value. */
#[Api(ApiStatus::Experimental)]
final class InvalidNetworkEgressContractException extends NetworkEgressContractException
{
    private const array IDENTITY_VIOLATIONS = [
        'operation_identity_grammar',
        'attempt_identity_grammar',
        'receipt_identity_grammar',
        'authority_identity_grammar',
        'policy_identity_grammar',
    ];
    private const array ORIGIN_VIOLATIONS = [
        'origin_host_grammar',
        'origin_scheme_grammar',
        'origin_port_range',
        'uri_scheme_grammar',
        'uri_user_info_present',
        'uri_fragment_present',
        'uri_host_missing',
        'uri_host_grammar',
        'uri_port_range',
    ];
    private const array REFERENCE_VIOLATIONS = [
        'authority_revision_range',
        'policy_digest_grammar',
    ];
    private const array BUDGET_VIOLATIONS = [
        'connection_attempts_range',
        'redirects_range',
        'resolution_candidates_range',
        'request_bytes_range',
        'response_bytes_range',
        'header_count_range',
        'header_bytes_range',
        'buffered_bytes_range',
        'evidence_bytes_range',
        'connect_timeout_range',
        'first_byte_timeout_range',
        'total_timeout_range',
        'connect_timeout_exceeds_total',
        'first_byte_timeout_exceeds_total',
    ];
    private const array CAPABILITY_VIOLATIONS = [
        'capabilities_not_list',
        'capability_count',
        'invalid_capability_entry',
        'duplicate_capability',
        'non_canonical_capability_order',
    ];
    private const array OPERATION_VIOLATIONS = [
        'request_origin_mismatch',
    ];
    private const array OUTCOME_VIOLATIONS = [
        'cancellation_sequence_range',
        'failure_reason_grammar',
        'transfer_byte_range',
        'transfer_state_relation',
        'attempt_count_range',
        'before_attempt_relation',
        'success_relation',
        'cancelled_after_effect_relation',
        'uncertain_effect_relation',
        'result_shape',
    ];
    private const array LIMIT_VIOLATIONS = [
        'origin_host_bytes',
        'connection_attempts',
        'redirects',
        'resolution_candidates',
        'request_bytes',
        'response_bytes',
        'header_count',
        'header_bytes',
        'buffered_bytes',
        'evidence_bytes',
        'connect_timeout_milliseconds',
        'first_byte_timeout_milliseconds',
        'total_timeout_milliseconds',
        'cancellation_observation_sequence',
        'capability_count',
        'failure_reason_bytes',
        'transfer_request_bytes',
        'transfer_response_bytes',
    ];

    public static function invalidIdentity(string $violation, ?Throwable $previous = null): self
    {
        return new self(NetworkEgressContractDiagnostic::InvalidIdentity, ['violation' => self::safeViolation($violation, self::IDENTITY_VIOLATIONS)], $previous);
    }

    public static function invalidOrigin(string $violation, ?Throwable $previous = null): self
    {
        return new self(NetworkEgressContractDiagnostic::InvalidOrigin, ['violation' => self::safeViolation($violation, self::ORIGIN_VIOLATIONS)], $previous);
    }

    public static function invalidReference(string $violation, ?Throwable $previous = null): self
    {
        return new self(NetworkEgressContractDiagnostic::InvalidReference, ['violation' => self::safeViolation($violation, self::REFERENCE_VIOLATIONS)], $previous);
    }

    public static function invalidBudget(string $violation, ?int $limit = null, ?Throwable $previous = null): self
    {
        $context = ['violation' => self::safeViolation($violation, self::BUDGET_VIOLATIONS)];
        if (null !== $limit) {
            $context['limit'] = max(0, $limit);
        }

        return new self(NetworkEgressContractDiagnostic::InvalidBudget, $context, $previous);
    }

    public static function invalidCapabilityEvidence(string $violation, ?int $entryCount = null, ?int $entryPosition = null, ?Throwable $previous = null): self
    {
        return new self(NetworkEgressContractDiagnostic::InvalidCapabilityEvidence, self::context($violation, self::CAPABILITY_VIOLATIONS, $entryCount, $entryPosition), $previous);
    }

    public static function invalidOperation(string $violation, ?Throwable $previous = null): self
    {
        return new self(NetworkEgressContractDiagnostic::InvalidOperation, ['violation' => self::safeViolation($violation, self::OPERATION_VIOLATIONS)], $previous);
    }

    public static function invalidOutcomeOrReceipt(string $violation, ?string $outcome = null, ?string $state = null, ?Throwable $previous = null): self
    {
        $context = ['violation' => self::safeViolation($violation, self::OUTCOME_VIOLATIONS)];
        if (null !== $outcome) {
            $context['outcome'] = self::safeOutcome($outcome);
        }
        if (null !== $state) {
            $context['state'] = self::safeState($state);
        }

        return new self(NetworkEgressContractDiagnostic::InvalidOutcomeOrReceipt, $context, $previous);
    }

    public static function contractLimitExceeded(string $violation, int $limit, ?int $entryCount = null, ?Throwable $previous = null): self
    {
        $context = [
            'violation' => self::safeViolation($violation, self::LIMIT_VIOLATIONS),
            'limit' => max(0, $limit),
        ];
        if (null !== $entryCount) {
            $context['entry_count'] = max(0, $entryCount);
        }

        return new self(NetworkEgressContractDiagnostic::ContractLimitExceeded, $context, $previous);
    }

    /**
     * @param list<string> $allowed
     * @return array<string, string|int|bool>
     */
    private static function context(string $violation, array $allowed, ?int $entryCount, ?int $entryPosition): array
    {
        $context = ['violation' => self::safeViolation($violation, $allowed)];
        if (null !== $entryCount) {
            $context['entry_count'] = max(0, $entryCount);
        }
        if (null !== $entryPosition) {
            $context['entry_position'] = max(0, $entryPosition);
        }

        return $context;
    }

    /** @param list<string> $allowed */
    private static function safeViolation(string $violation, array $allowed): string
    {
        return in_array($violation, $allowed, true) ? $violation : 'unsupported_violation';
    }

    private static function safeOutcome(string $outcome): string
    {
        return in_array($outcome, ['rejected_before_attempt', 'succeeded', 'failed_before_effect', 'cancelled_before_effect', 'cancelled_after_possible_effect', 'uncertain_effect'], true)
            ? $outcome
            : 'unsupported_outcome';
    }

    private static function safeState(string $state): string
    {
        return in_array($state, ['none', 'request_partially_sent', 'request_sent', 'response_partially_received', 'response_received'], true)
            ? $state
            : 'unsupported_state';
    }
}
