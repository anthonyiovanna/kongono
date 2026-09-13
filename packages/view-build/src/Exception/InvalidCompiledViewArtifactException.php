<?php

declare(strict_types=1);

namespace Kongono\ViewBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewBuild\Diagnostic\ViewBuildDiagnostic;
use Throwable;

/** Reports malformed or inconsistent compiled evidence without disclosure. */
#[Api(ApiStatus::Experimental)]
final class InvalidCompiledViewArtifactException extends ViewBuildException
{
    private const array VIOLATIONS = ['metadata_framing', 'metadata_json', 'metadata_shape', 'schema_or_version', 'builder_contract', 'digest_grammar', 'model_invalid', 'model_digest_relation', 'generated_class_relation', 'generated_framing', 'generated_syntax', 'generated_forbidden_surface', 'generated_digest_relation', 'generated_regeneration', 'artifact_identity_relation'];

    public static function invalid(string $violation, ?Throwable $previous = null): self
    {
        return new self(ViewBuildDiagnostic::InvalidArtifact, ['violation' => in_array($violation, self::VIOLATIONS, true) ? $violation : 'unsupported_violation'], $previous);
    }
}
