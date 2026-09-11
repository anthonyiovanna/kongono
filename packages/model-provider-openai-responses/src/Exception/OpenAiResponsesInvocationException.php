<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelProviderOpenAiResponses\Diagnostic\OpenAiResponsesDiagnostic;
use Kongono\ModelProviderOpenAiResponses\Stream\ResponsesSseEventKind;
use Throwable;

/** Reports bounded one-shot invocation failures without disclosing provider data. */
#[Api(ApiStatus::Experimental)]
final class OpenAiResponsesInvocationException extends OpenAiResponsesException
{
    private const array CONTEXT_VIOLATIONS = [
        'adapter_identity', 'plan_identity', 'profile', 'unsupported_input',
        'credential_lifetime', 'endpoint', 'operation_identity', 'encoding',
        'application_profile', 'application_state', 'credential_material',
    ];
    private const array LIFECYCLE_VIOLATIONS = ['adapter_reused', 'session_pull', 'session_state', 'stream_session_pull', 'stream_session_state'];
    private const array EXTERNAL_VIOLATIONS = ['application_creation', 'application_run', 'operation_outcome', 'response_missing'];
    private const array HTTP_VIOLATIONS = ['status', 'content_type_missing', 'content_type_duplicate', 'content_type_invalid'];
    private const array BODY_VIOLATIONS = ['body_unavailable', 'body_unreadable', 'body_size', 'body_read', 'body_stalled', 'body_cleanup', 'combined_failure'];
    private const array NORMALIZATION_VIOLATIONS = ['decoded_response'];
    private const array STREAM_FRAMING_VIOLATIONS = [
        'body_unavailable', 'status', 'content_type_missing', 'content_type_duplicate', 'content_type_invalid',
        'body_unreadable', 'body_read', 'body_stalled', 'response_bytes', 'retained_unread_bytes', 'line_bytes',
        'bare_cr', 'utf8', 'bom', 'comment', 'field_name', 'event_field', 'data_field', 'unknown_field',
        'empty_frame', 'data_value', 'unterminated_frame', 'trailing_bytes',
    ];
    private const array STREAM_MAPPING_VIOLATIONS = [
        'json', 'duplicate_member', 'event_object', 'event_members', 'event_kind', 'event_name', 'object_members',
        'object', 'response_snapshot', 'message_added', 'content_part', 'delta', 'text_snapshot', 'message_done',
        'message_identity', 'index', 'terminal_response', 'failed_response',
    ];
    private const array STREAM_ORDER_VIOLATIONS = [
        'provider_events', 'provider_sequence', 'event_order', 'delta_bytes', 'text_snapshot', 'terminal_snapshot', 'terminal_missing',
    ];

    private ?Throwable $cleanupFailure = null;

    public static function invalidContext(string $violation, ?Throwable $previous = null): self
    {
        return new self(OpenAiResponsesDiagnostic::InvalidInvocationContext, [
            'stage' => 'open',
            'violation' => self::safeToken($violation, self::CONTEXT_VIOLATIONS, 'unsupported_violation'),
        ], $previous);
    }

    public static function lifecycle(string $violation, ?Throwable $previous = null): self
    {
        return new self(OpenAiResponsesDiagnostic::InvocationLifecycleViolation, [
            'stage' => 'lifecycle',
            'violation' => self::safeToken($violation, self::LIFECYCLE_VIOLATIONS, 'unsupported_violation'),
        ], $previous);
    }

    public static function externalService(string $violation, ?Throwable $previous = null): self
    {
        return new self(OpenAiResponsesDiagnostic::ExternalServiceOperationFailed, [
            'stage' => 'external_service',
            'violation' => self::safeToken($violation, self::EXTERNAL_VIOLATIONS, 'unsupported_violation'),
        ], $previous);
    }

    public static function httpResponse(string $violation, ?int $status = null, ?Throwable $previous = null): self
    {
        $context = [
            'stage' => 'http_response',
            'violation' => self::safeToken($violation, self::HTTP_VIOLATIONS, 'unsupported_violation'),
        ];
        if (null !== $status) {
            $context['http_status'] = max(0, min(999, $status));
        }

        return new self(OpenAiResponsesDiagnostic::HttpResponseRejected, $context, $previous);
    }

    public static function responseBody(
        string $violation,
        ?Throwable $previous = null,
        ?Throwable $cleanupFailure = null,
        ?int $limit = null,
    ): self {
        $context = [
            'stage' => 'response_body',
            'violation' => self::safeToken($violation, self::BODY_VIOLATIONS, 'unsupported_violation'),
        ];
        if (null !== $limit) {
            $context['limit'] = max(0, $limit);
        }
        $failure = new self(OpenAiResponsesDiagnostic::ResponseBodyFailed, $context, $previous);
        $failure->cleanupFailure = $cleanupFailure;

        return $failure;
    }

    public static function normalization(string $violation, ?Throwable $previous = null): self
    {
        return new self(OpenAiResponsesDiagnostic::ResultNormalizationFailed, [
            'stage' => 'normalization',
            'violation' => self::safeToken($violation, self::NORMALIZATION_VIOLATIONS, 'unsupported_violation'),
        ], $previous);
    }

    public static function streamingFraming(
        string $violation,
        ?int $limit = null,
        ?int $observedCount = null,
        ?ResponsesSseEventKind $eventKind = null,
        ?Throwable $previous = null,
    ): self {
        return self::streamFailure(OpenAiResponsesDiagnostic::StreamingResponseFramingRejected, 'framing', $violation, self::STREAM_FRAMING_VIOLATIONS, $limit, $observedCount, $eventKind, $previous);
    }

    public static function streamingMapping(
        string $violation,
        ?int $limit = null,
        ?int $observedCount = null,
        ?ResponsesSseEventKind $eventKind = null,
        ?Throwable $previous = null,
    ): self {
        return self::streamFailure(OpenAiResponsesDiagnostic::StreamingEventMappingRejected, 'mapping', $violation, self::STREAM_MAPPING_VIOLATIONS, $limit, $observedCount, $eventKind, $previous);
    }

    public static function streamingOrder(
        string $violation,
        ?int $limit = null,
        ?int $observedCount = null,
        ?ResponsesSseEventKind $eventKind = null,
        ?Throwable $previous = null,
    ): self {
        return self::streamFailure(OpenAiResponsesDiagnostic::StreamingEventOrderRejected, 'order', $violation, self::STREAM_ORDER_VIOLATIONS, $limit, $observedCount, $eventKind, $previous);
    }

    public static function streamingCleanup(?Throwable $primary, Throwable $cleanup): self
    {
        $failure = self::streamFailure(
            OpenAiResponsesDiagnostic::StreamingResponseCleanupFailed,
            'cleanup',
            null === $primary ? 'cleanup_failure' : 'combined_failure',
            ['cleanup_failure', 'combined_failure'],
            previous: $primary,
        );
        $failure->cleanupFailure = $cleanup;

        return $failure;
    }

    /** @param list<string> $allowed */
    private static function streamFailure(
        OpenAiResponsesDiagnostic $diagnostic,
        string $stage,
        string $violation,
        array $allowed,
        ?int $limit = null,
        ?int $observedCount = null,
        ?ResponsesSseEventKind $eventKind = null,
        ?Throwable $previous = null,
    ): self {
        $context = [
            'violation' => self::safeToken($violation, $allowed, 'unsupported_violation'),
            'stage' => $stage,
        ];
        if (null !== $limit) {
            $context['limit'] = max(0, $limit);
        }
        if (null !== $observedCount) {
            $context['observed_count'] = max(0, $observedCount);
        }
        if (null !== $eventKind) {
            $context['event_kind'] = match ($eventKind) {
                ResponsesSseEventKind::ResponseCreated => 'response_created',
                ResponsesSseEventKind::ResponseInProgress => 'response_in_progress',
                ResponsesSseEventKind::OutputItemAdded => 'output_item_added',
                ResponsesSseEventKind::ContentPartAdded => 'content_part_added',
                ResponsesSseEventKind::OutputTextDelta => 'output_text_delta',
                ResponsesSseEventKind::OutputTextDone => 'output_text_done',
                ResponsesSseEventKind::ContentPartDone => 'content_part_done',
                ResponsesSseEventKind::OutputItemDone => 'output_item_done',
                ResponsesSseEventKind::ResponseCompleted => 'response_completed',
                ResponsesSseEventKind::ResponseIncomplete => 'response_incomplete',
                ResponsesSseEventKind::ResponseFailed => 'response_failed',
            };
        }

        return new self($diagnostic, $context, $previous);
    }

    /** Programmatic cleanup cause; never included in safe diagnostic rendering. */
    public function cleanupFailure(): ?Throwable
    {
        return $this->cleanupFailure;
    }
}
