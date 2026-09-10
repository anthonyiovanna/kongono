<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceBindingBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceBindingBuild\Diagnostic\ExternalServiceBindingBuildDiagnostic;
use Throwable;

/** Reports malformed generated binding metadata template values or bytes. */
#[Api(ApiStatus::Experimental)]
final class InvalidGeneratedExternalServiceBindingsException extends ExternalServiceBindingBuildException
{
    public static function invalid(string $violation, ?string $field = null, ?int $count = null, ?int $position = null, ?int $limit = null, ?int $artifactBytes = null, ?Throwable $previous = null): self
    {
        $violations = ['generated_bytes', 'generated_header', 'metadata_json', 'metadata_shape', 'schema_or_version', 'generator_contract', 'implementation_mismatch', 'source_invalid', 'digest_grammar', 'artifact_mismatch', 'template_mismatch'];
        $fields = ['schema', 'version', 'contract_version', 'generator', 'source_identity', 'source_sha256', 'binding_artifact_sha256', 'bindings', 'implementation_sha256'];
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
            $context['limit'] = max(0, $limit);
        }
        if (null !== $artifactBytes) {
            $context['artifact_bytes'] = max(0, $artifactBytes);
        }

        return new self(ExternalServiceBindingBuildDiagnostic::InvalidGeneratedPhp, $context, $previous);
    }

    public static function limitExceeded(int $bytes, int $limit): self
    {
        return new self(ExternalServiceBindingBuildDiagnostic::GeneratedPhpLimitExceeded, ['violation' => 'artifact_bytes', 'artifact_bytes' => max(0, $bytes), 'limit' => max(0, $limit)]);
    }
}
