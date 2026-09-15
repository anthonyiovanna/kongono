<?php

declare(strict_types=1);

namespace Kongono\AssetBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetBuild\Diagnostic\AssetBuildDiagnostic;
use Throwable;

/** Reports one invalid native output or canonical artifact relation. */
#[Api(ApiStatus::Experimental)]
final class InvalidAssetBuildArtifactException extends AssetBuildException
{
    public static function output(string $violation, ?Throwable $previous = null): self
    {
        return new self(AssetBuildDiagnostic::InvalidBuildOutput, ['violation' => self::safe($violation)], $previous);
    }

    public static function invalid(string $violation, ?Throwable $previous = null): self
    {
        return new self(AssetBuildDiagnostic::InvalidArtifact, ['violation' => self::safe($violation)], $previous);
    }

    private static function safe(string $value): string
    {
        return in_array($value, ['identity_grammar', 'output_path_grammar', 'output_path_relation', 'output_identity_relation', 'output_metadata_relation', 'outputs_not_list', 'output_map_shape', 'duplicate_output', 'artifact_framing', 'artifact_schema', 'artifact_canonical', 'artifact_identity_relation', 'graph_identity_relation', 'output_evidence_relation', 'output_bound'], true) ? $value : 'invalid_artifact';
    }
}
