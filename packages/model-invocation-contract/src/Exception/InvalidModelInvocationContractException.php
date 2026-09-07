<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\Diagnostic\ModelInvocationContractDiagnostic;
use Throwable;

/** Reports one bounded failure to construct an exact contract value. */
#[Api(ApiStatus::Experimental)]
final class InvalidModelInvocationContractException extends ModelInvocationContractException
{
    private const array IDENTITY_VIOLATIONS = ['provider_identity_grammar', 'model_identity_grammar', 'profile_identity_grammar', 'invocation_identity_grammar', 'result_identity_grammar', 'action_name_grammar', 'usage_metric_name_grammar', 'usage_unit_name_grammar', 'receipt_identity_grammar'];
    private const array PROFILE_VIOLATIONS = ['capabilities_not_list', 'capability_count', 'invalid_capability_entry', 'duplicate_capability', 'non_canonical_capability_order'];
    private const array INPUT_VIOLATIONS = ['input_content_grammar'];
    private const array REQUEST_VIOLATIONS = ['messages_not_list', 'message_count', 'invalid_message_entry', 'message_aggregate_bytes', 'unsupported_result_mode', 'streaming_capability_required', 'invocation_limit_range'];
    private const array RESULT_VIOLATIONS = ['result_text_grammar', 'action_arguments_grammar', 'result_shape', 'finish_reason_shape', 'usage_capability_required', 'cost_capability_required'];
    private const array STREAM_VIOLATIONS = ['stream_sequence_range', 'stream_payload_grammar', 'stream_event_shape'];
    private const array USAGE_COST_VIOLATIONS = ['usage_quantity_range', 'usage_not_list', 'usage_count', 'invalid_usage_entry', 'duplicate_usage_entry', 'non_canonical_usage_order', 'currency_code_grammar', 'decimal_amount_grammar'];
    private const array RECEIPT_VIOLATIONS = ['attempt_count_range', 'rejected_attempt_relation', 'outcome_attempt_relation', 'result_outcome_relation', 'usage_capability_required', 'cost_capability_required'];
    private const array LIMIT_VIOLATIONS = ['input_content_bytes', 'result_text_bytes', 'action_arguments_bytes', 'stream_payload_bytes', 'message_count', 'message_aggregate_bytes', 'usage_count'];

    public static function invalidIdentity(string $violation, ?Throwable $previous = null): self
    {
        return new self(ModelInvocationContractDiagnostic::InvalidIdentity, ['violation' => self::safeViolation($violation, self::IDENTITY_VIOLATIONS)], $previous);
    }

    public static function invalidProfile(string $violation, ?int $entryCount = null, ?int $entryPosition = null, ?Throwable $previous = null): self
    {
        return new self(ModelInvocationContractDiagnostic::InvalidProfile, self::context($violation, self::PROFILE_VIOLATIONS, $entryCount, $entryPosition), $previous);
    }

    public static function invalidInput(string $violation, ?Throwable $previous = null): self
    {
        return new self(ModelInvocationContractDiagnostic::InvalidInput, ['violation' => self::safeViolation($violation, self::INPUT_VIOLATIONS)], $previous);
    }

    public static function invalidRequest(string $violation, ?int $entryCount = null, ?int $entryPosition = null, ?string $mode = null, ?Throwable $previous = null): self
    {
        $context = self::context($violation, self::REQUEST_VIOLATIONS, $entryCount, $entryPosition);
        if (null !== $mode) {
            $context['mode'] = self::safeMode($mode);
        }

        return new self(ModelInvocationContractDiagnostic::InvalidRequest, $context, $previous);
    }

    public static function invalidResult(string $violation, ?string $mode = null, ?Throwable $previous = null): self
    {
        $context = ['violation' => self::safeViolation($violation, self::RESULT_VIOLATIONS)];
        if (null !== $mode) {
            $context['mode'] = self::safeMode($mode);
        }

        return new self(ModelInvocationContractDiagnostic::InvalidResult, $context, $previous);
    }

    public static function invalidStreamEvent(string $violation, ?string $eventKind = null, ?Throwable $previous = null): self
    {
        $context = ['violation' => self::safeViolation($violation, self::STREAM_VIOLATIONS)];
        if (null !== $eventKind) {
            $context['event_kind'] = self::safeEventKind($eventKind);
        }

        return new self(ModelInvocationContractDiagnostic::InvalidStreamEvent, $context, $previous);
    }

    public static function invalidUsageOrCostEvidence(string $violation, ?int $entryCount = null, ?int $entryPosition = null, ?Throwable $previous = null): self
    {
        return new self(ModelInvocationContractDiagnostic::InvalidUsageOrCostEvidence, self::context($violation, self::USAGE_COST_VIOLATIONS, $entryCount, $entryPosition), $previous);
    }

    public static function invalidReceipt(string $violation, ?Throwable $previous = null): self
    {
        return new self(ModelInvocationContractDiagnostic::InvalidReceipt, ['violation' => self::safeViolation($violation, self::RECEIPT_VIOLATIONS)], $previous);
    }

    public static function contractLimitExceeded(string $violation, int $limit, ?int $entryCount = null, ?Throwable $previous = null): self
    {
        $context = ['violation' => self::safeViolation($violation, self::LIMIT_VIOLATIONS), 'limit' => max(0, $limit)];
        if (null !== $entryCount) {
            $context['entry_count'] = max(0, $entryCount);
        }

        return new self(ModelInvocationContractDiagnostic::ContractLimitExceeded, $context, $previous);
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

    private static function safeMode(string $mode): string
    {
        return in_array($mode, ['text', 'structured', 'action_suggestion'], true) ? $mode : 'unsupported_mode';
    }

    private static function safeEventKind(string $kind): string
    {
        return in_array($kind, ['started', 'content_delta', 'action_name', 'action_arguments_delta', 'usage', 'completed', 'failed'], true) ? $kind : 'unsupported_event_kind';
    }
}
