<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationBuild\Diagnostic\ModelInvocationBuildDiagnostic;

/** Reports one bounded typed invocation-plan source failure. */
#[Api(ApiStatus::Experimental)]
final class InvalidInvocationPlanSourceException extends ModelInvocationBuildException
{
    public static function invalidPlanIdentity(string $violation): self
    {
        return new self(ModelInvocationBuildDiagnostic::InvalidPlanIdentity, self::context($violation));
    }

    public static function invalidAdapterIdentity(string $violation): self
    {
        return new self(ModelInvocationBuildDiagnostic::InvalidAdapterIdentity, self::context($violation));
    }

    public static function invalidBudget(
        string $violation,
        ?string $field = null,
        ?int $count = null,
        ?int $position = null,
        ?int $limit = null,
    ): self {
        return new self(ModelInvocationBuildDiagnostic::InvalidExecutionBudget, self::context($violation, $field, $count, $position, $limit));
    }

    public static function invalidSource(string $violation, ?string $field = null): self
    {
        return new self(ModelInvocationBuildDiagnostic::InvalidSource, self::context($violation, $field));
    }

    /** @return array<string, string|int|bool> */
    private static function context(
        string $violation,
        ?string $field = null,
        ?int $count = null,
        ?int $position = null,
        ?int $limit = null,
    ): array {
        $violations = [
            'identity_grammar', 'duration_range', 'attempt_count', 'retained_evidence_range',
            'usage_limits_not_list', 'usage_limits_count', 'invalid_usage_limit',
            'noncanonical_usage_limit_order', 'usage_maximum_quantity_range', 'contract_version',
        ];
        $fields = ['max_duration_milliseconds', 'max_attempts', 'max_retained_evidence_bytes', 'usage_limits', 'maximum_quantity', 'contract_version'];
        $context = ['violation' => in_array($violation, $violations, true) ? $violation : 'unsupported_violation'];
        if (null !== $field) {
            $context['field'] = in_array($field, $fields, true) ? $field : 'unsupported_field';
        }
        if (null !== $count) {
            $context['entry_count'] = max(0, $count);
        }
        if (null !== $position) {
            $context['entry_position'] = max(0, $position);
        }
        if (null !== $limit) {
            $context['numeric_limit'] = max(0, $limit);
        }

        return $context;
    }
}
