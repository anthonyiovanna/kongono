<?php

declare(strict_types=1);

namespace Kongono\ViewContract\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewContract\Diagnostic\ViewContractDiagnostic;
use Throwable;

/** Reports one bounded failure to construct or validate contract evidence. */
#[Api(ApiStatus::Experimental)]
final class InvalidViewContractException extends ViewContractException
{
    private const array IDENTITY_VIOLATIONS = ['logical_view_identity_grammar', 'compiled_view_identity_grammar', 'render_operation_identity_grammar'];
    private const array VALUE_VIOLATIONS = ['presentation_text_grammar', 'values_not_list', 'invalid_value_entry', 'field_name_grammar', 'invalid_field_entry'];
    private const array SCHEMA_VIOLATIONS = ['string_max_bytes_range', 'list_max_items_range', 'fields_not_list', 'field_count', 'invalid_field_schema', 'duplicate_field', 'context_root_not_record', 'invalid_schema_shape'];
    private const array MISMATCH_VIOLATIONS = ['wrong_value_kind', 'string_bound_exceeded', 'list_bound_exceeded', 'list_item_mismatch', 'required_field_missing', 'undeclared_field', 'field_value_mismatch', 'invalid_schema_shape', 'unsupported_value_implementation'];
    private const array LIMIT_VIOLATIONS = ['presentation_text_bytes', 'list_entry_count', 'record_field_count', 'value_depth', 'value_node_count', 'schema_depth', 'schema_node_count', 'rendered_output_bytes'];
    private const array RENDER_LIMIT_VIOLATIONS = ['max_output_bytes_range', 'max_render_steps_range'];
    private const array REQUEST_VIOLATIONS = ['context_schema_mismatch'];
    private const array RESULT_VIOLATIONS = ['rendered_output_grammar', 'render_step_count_range', 'escaped_value_count_relation', 'output_metadata_relation'];
    private const array FAILURE_VIOLATIONS = ['failure_state_relation'];
    private const array VALUE_KINDS = ['string', 'integer', 'boolean', 'null', 'list', 'record'];
    private const array STATES = ['requested', 'rendering', 'completed', 'failed', 'retired'];

    public static function invalidIdentity(string $violation, ?Throwable $previous = null): self
    {
        return new self(ViewContractDiagnostic::InvalidIdentity, ['violation' => self::safe($violation, self::IDENTITY_VIOLATIONS)], $previous);
    }

    public static function invalidPresentationValue(string $violation, ?int $entryCount = null, ?int $entryPosition = null, ?Throwable $previous = null): self
    {
        return new self(ViewContractDiagnostic::InvalidPresentationValue, self::context($violation, self::VALUE_VIOLATIONS, $entryCount, $entryPosition), $previous);
    }

    public static function invalidPresentationSchema(string $violation, ?int $entryCount = null, ?int $entryPosition = null, ?int $limit = null, ?int $depth = null, ?Throwable $previous = null): self
    {
        return new self(ViewContractDiagnostic::InvalidPresentationSchema, self::context($violation, self::SCHEMA_VIOLATIONS, $entryCount, $entryPosition, $limit, $depth), $previous);
    }

    public static function contextSchemaMismatch(string $violation, ?int $entryCount = null, ?int $entryPosition = null, ?int $limit = null, ?int $depth = null, ?string $valueKind = null, ?string $state = null, ?Throwable $previous = null): self
    {
        return new self(ViewContractDiagnostic::ContextSchemaMismatch, self::context($violation, self::MISMATCH_VIOLATIONS, $entryCount, $entryPosition, $limit, $depth, $valueKind, $state), $previous);
    }

    public static function invalidRenderLimits(string $violation, ?int $limit = null, ?Throwable $previous = null): self
    {
        return new self(ViewContractDiagnostic::InvalidRenderLimits, self::context($violation, self::RENDER_LIMIT_VIOLATIONS, limit: $limit), $previous);
    }

    public static function invalidRenderRequest(string $violation, ?string $state = null, ?Throwable $previous = null): self
    {
        return new self(ViewContractDiagnostic::InvalidRenderRequest, self::context($violation, self::REQUEST_VIOLATIONS, state: $state), $previous);
    }

    public static function invalidRenderResult(string $violation, ?int $limit = null, ?string $state = null, ?Throwable $previous = null): self
    {
        return new self(ViewContractDiagnostic::InvalidRenderResult, self::context($violation, self::RESULT_VIOLATIONS, limit: $limit, state: $state), $previous);
    }

    public static function invalidRenderFailure(string $violation, ?string $state = null, ?Throwable $previous = null): self
    {
        return new self(ViewContractDiagnostic::InvalidRenderFailure, self::context($violation, self::FAILURE_VIOLATIONS, state: $state), $previous);
    }

    public static function contractBoundExceeded(string $violation, int $limit, ?int $entryCount = null, ?int $depth = null, ?Throwable $previous = null): self
    {
        return new self(ViewContractDiagnostic::ContractBoundExceeded, self::context($violation, self::LIMIT_VIOLATIONS, $entryCount, limit: max(0, $limit), depth: $depth), $previous);
    }

    /**
     * @param list<string> $allowed
     * @return array<string, string|int|bool>
     */
    private static function context(string $violation, array $allowed, ?int $entryCount = null, ?int $entryPosition = null, ?int $limit = null, ?int $depth = null, ?string $valueKind = null, ?string $state = null): array
    {
        $context = ['violation' => self::safe($violation, $allowed)];
        if (null !== $entryCount) {
            $context['entry_count'] = max(0, $entryCount);
        }
        if (null !== $entryPosition) {
            $context['entry_position'] = max(0, $entryPosition);
        }
        if (null !== $limit) {
            $context['limit'] = max(0, $limit);
        }
        if (null !== $depth) {
            $context['depth'] = max(0, $depth);
        }
        if (null !== $valueKind) {
            $context['value_kind'] = self::safeToken($valueKind, self::VALUE_KINDS, 'unsupported_value_kind');
        }
        if (null !== $state) {
            $context['state'] = self::safeToken($state, self::STATES, 'unsupported_state');
        }

        return $context;
    }

    /** @param list<string> $allowed */
    private static function safe(string $value, array $allowed): string
    {
        return self::safeToken($value, $allowed, 'unsupported_violation');
    }

    /** @param list<string> $allowed */
    private static function safeToken(string $value, array $allowed, string $fallback): string
    {
        return in_array($value, $allowed, true) ? $value : $fallback;
    }
}
