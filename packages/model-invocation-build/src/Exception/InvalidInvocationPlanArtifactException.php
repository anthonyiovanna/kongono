<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationBuild\Diagnostic\ModelInvocationBuildDiagnostic;
use Throwable;

/** Reports malformed invocation-plan artifact structure, values, or bounds. */
#[Api(ApiStatus::Experimental)]
final class InvalidInvocationPlanArtifactException extends ModelInvocationBuildException
{
    public static function invalid(
        string $violation,
        ?string $field = null,
        ?int $count = null,
        ?int $position = null,
        ?int $limit = null,
        ?int $artifactBytes = null,
        ?Throwable $previous = null,
    ): self {
        $violations = [
            'artifact_bytes', 'builder_contract', 'capability_compatibility', 'digest_grammar',
            'encoding_failed', 'identity_grammar', 'invalid_json', 'invalid_list', 'invalid_scalar',
            'invalid_shape', 'noncanonical_json', 'noncanonical_order', 'schema_or_version',
        ];
        $fields = [
            'schema', 'artifact_version', 'contract_version', 'builder', 'plan', 'plan_id', 'adapter_id',
            'invocation_id', 'profile', 'profile_id', 'provider_id', 'model_id', 'capabilities', 'request',
            'request_sha256', 'result_mode', 'streaming', 'limits', 'max_output_bytes', 'max_stream_events',
            'max_usage_entries', 'budget', 'max_duration_milliseconds', 'max_attempts',
            'max_retained_evidence_bytes', 'usage_limits', 'metric', 'unit', 'maximum_quantity',
            'cost_limit', 'currency', 'maximum_amount', 'source_sha256',
        ];
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
        if (null !== $artifactBytes) {
            $context['artifact_bytes'] = max(0, $artifactBytes);
        }

        return new self(ModelInvocationBuildDiagnostic::InvalidArtifact, $context, $previous);
    }
}
