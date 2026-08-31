<?php

declare(strict_types=1);

namespace Kongono\ExtensionBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionBuild\Diagnostic\ExtensionBuildDiagnostic;
use Throwable;

/** Reports malformed declaration input, artifact structure, or fixed bounds. */
#[Api(ApiStatus::Experimental)]
final class InvalidExtensionBuildException extends ExtensionBuildException
{
    public static function input(
        string $violation,
        string $kind = 'declaration',
        ?int $entryCount = null,
        ?int $entryPosition = null,
        ?Throwable $previous = null,
    ): self {
        return new self(ExtensionBuildDiagnostic::InvalidBuildInput, self::context($violation, $kind, $entryCount, $entryPosition), $previous);
    }

    public static function artifact(
        string $violation,
        string $kind = 'artifact',
        ?int $entryCount = null,
        ?int $entryPosition = null,
        ?int $artifactBytes = null,
        ?Throwable $previous = null,
    ): self {
        return new self(ExtensionBuildDiagnostic::InvalidArtifact, self::context($violation, $kind, $entryCount, $entryPosition, $artifactBytes), $previous);
    }

    public static function limit(
        string $violation,
        string $kind,
        ?int $entryCount = null,
        ?int $artifactBytes = null,
    ): self {
        return new self(ExtensionBuildDiagnostic::BuildLimitExceeded, self::context($violation, $kind, $entryCount, null, $artifactBytes));
    }

    /** @return array<string, string|int|bool> */
    private static function context(
        string $violation,
        string $kind,
        ?int $entryCount = null,
        ?int $entryPosition = null,
        ?int $artifactBytes = null,
    ): array {
        $context = [
            'violation' => in_array($violation, self::violations(), true) ? $violation : 'unsupported_violation',
            'kind' => in_array($kind, ['artifact', 'builder', 'capability', 'contribution', 'declaration', 'field', 'identity', 'provenance'], true) ? $kind : 'unsupported_kind',
        ];
        if (null !== $entryCount) {
            $context['entry_count'] = max(0, $entryCount);
        }
        if (null !== $entryPosition) {
            $context['entry_position'] = max(0, $entryPosition);
        }
        if (null !== $artifactBytes) {
            $context['artifact_bytes'] = max(0, $artifactBytes);
        }

        return $context;
    }

    /** @return list<string> */
    private static function violations(): array
    {
        return [
            'artifact_bytes', 'artifact_rebuild_mismatch', 'builder_contract', 'count_mismatch',
            'declaration_count', 'declaration_not_list', 'declaration_readmission_failed',
            'declaration_set_digest_mismatch', 'declaration_set_identity_grammar',
            'declaration_set_identity_mismatch', 'digest_grammar', 'duplicate_extension_identity',
            'invalid_declaration_entry', 'invalid_json', 'invalid_scalar', 'invalid_shape',
            'noncanonical_declaration_order', 'noncanonical_json', 'schema_or_version',
        ];
    }
}
