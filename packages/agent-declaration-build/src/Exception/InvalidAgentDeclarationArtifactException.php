<?php

declare(strict_types=1);

namespace Kongono\AgentDeclarationBuild\Exception;

use Kongono\AgentDeclarationBuild\Diagnostic\AgentDeclarationBuildDiagnostic;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Reports malformed artifact structure, canonical bytes, digests, or bounds. */
#[Api(ApiStatus::Experimental)]
final class InvalidAgentDeclarationArtifactException extends AgentDeclarationBuildException
{
    public static function invalid(
        string $violation,
        ?int $count = null,
        ?int $position = null,
        ?int $artifactBytes = null,
        ?Throwable $previous = null,
    ): self {
        return new self(AgentDeclarationBuildDiagnostic::InvalidArtifact, self::context($violation, $count, $position, $artifactBytes), $previous);
    }

    public static function limit(string $violation, int $artifactBytes): self
    {
        return new self(AgentDeclarationBuildDiagnostic::BuildLimitExceeded, self::context($violation, artifactBytes: $artifactBytes));
    }

    /** @return array<string, string|int|bool> */
    private static function context(string $violation, ?int $count = null, ?int $position = null, ?int $artifactBytes = null): array
    {
        $allowed = [
            'artifact_bytes', 'artifact_rebuild_mismatch', 'builder_contract', 'declaration_digest_mismatch',
            'declaration_readmission_failed', 'digest_grammar', 'encoding_failed', 'invalid_json', 'invalid_list',
            'invalid_scalar', 'invalid_shape', 'noncanonical_json', 'schema_or_version',
        ];
        $context = ['violation' => in_array($violation, $allowed, true) ? $violation : 'unsupported_violation'];
        if (null !== $count) {
            $context['entry_count'] = max(0, $count);
        }
        if (null !== $position) {
            $context['entry_position'] = max(0, $position);
        }
        if (null !== $artifactBytes) {
            $context['artifact_bytes'] = max(0, $artifactBytes);
        }

        return $context;
    }
}
