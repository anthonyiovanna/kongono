<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressPolicyBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressPolicyBuild\Diagnostic\NetworkEgressPolicyBuildDiagnostic;
use Throwable;

/** Reports malformed canonical policy artifact structure values or digests. */
#[Api(ApiStatus::Experimental)]
final class InvalidEgressPolicyArtifactException extends NetworkEgressPolicyBuildException
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
            'encoding_failed', 'invalid_json', 'noncanonical_json', 'invalid_shape', 'schema_or_version',
            'builder_contract', 'invalid_scalar', 'invalid_list', 'identity_grammar', 'origin_grammar',
            'budget_invalid', 'capability_invalid', 'compatibility_invalid', 'digest_grammar', 'source_digest_mismatch',
        ];
        $fields = [
            'schema', 'artifact_version', 'contract_version', 'builder', 'policy', 'policy_id', 'allowed_origins',
            'scheme', 'host', 'port', 'address_scope', 'transport_security', 'redirect_mode', 'proxy_mode',
            'cookie_mode', 'credential_mode', 'budget', 'maximum_connection_attempts', 'maximum_redirects',
            'maximum_resolution_candidates', 'maximum_request_bytes', 'maximum_response_bytes', 'maximum_header_count',
            'maximum_aggregate_header_bytes', 'maximum_buffered_bytes', 'maximum_retained_evidence_bytes',
            'connect_timeout_milliseconds', 'first_byte_timeout_milliseconds', 'total_timeout_milliseconds',
            'required_capabilities', 'source_sha256',
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

        return new self(NetworkEgressPolicyBuildDiagnostic::InvalidPolicyArtifact, $context, $previous);
    }

    public static function limitExceeded(int $artifactBytes, int $limit): self
    {
        return new self(NetworkEgressPolicyBuildDiagnostic::PolicyBuildLimitExceeded, [
            'violation' => 'artifact_bytes',
            'artifact_bytes' => max(0, $artifactBytes),
            'numeric_limit' => max(0, $limit),
        ]);
    }
}
