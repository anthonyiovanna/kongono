<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceBindingBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceBindingBuild\Diagnostic\ExternalServiceBindingBuildDiagnostic;
use Throwable;

/** Reports malformed canonical binding artifact structure values or digests. */
#[Api(ApiStatus::Experimental)]
final class InvalidExternalServiceBindingArtifactException extends ExternalServiceBindingBuildException
{
    public static function invalid(string $violation, ?string $field = null, ?int $count = null, ?int $position = null, ?int $limit = null, ?int $artifactBytes = null, ?Throwable $previous = null): self
    {
        $violations = ['encoding_failed', 'invalid_json', 'noncanonical_json', 'invalid_shape', 'schema_or_version', 'builder_contract', 'invalid_scalar', 'invalid_list', 'source_invalid', 'binding_invalid', 'endpoint_invalid', 'capability_invalid', 'digest_grammar', 'source_digest_mismatch'];
        $fields = ['schema', 'artifact_version', 'contract_version', 'builder', 'source', 'source_identity', 'bindings', 'binding_identity', 'service_profile_identity', 'endpoint', 'scheme', 'host', 'port', 'base_path', 'credential_reference', 'credential_source_identity', 'required_capabilities', 'source_sha256'];
        $context = self::context($violation, $field, $count, $position, $limit, $artifactBytes, $violations, $fields);

        return new self(ExternalServiceBindingBuildDiagnostic::InvalidArtifact, $context, $previous);
    }

    public static function limitExceeded(int $artifactBytes, int $limit): self
    {
        return new self(ExternalServiceBindingBuildDiagnostic::ArtifactLimitExceeded, ['violation' => 'artifact_bytes', 'artifact_bytes' => max(0, $artifactBytes), 'limit' => max(0, $limit)]);
    }

    /** @param list<string> $violations
     * @param list<string> $fields
     * @return array<string, string|int|bool>
     */
    private static function context(string $violation, ?string $field, ?int $count, ?int $position, ?int $limit, ?int $artifactBytes, array $violations, array $fields): array
    {
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

        return $context;
    }
}
