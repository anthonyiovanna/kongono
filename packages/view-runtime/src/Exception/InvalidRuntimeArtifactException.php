<?php

declare(strict_types=1);

namespace Kongono\ViewRuntime\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewRuntime\Diagnostic\ViewRuntimeDiagnostic;
use Throwable;

/** Reports invalid or unsafe compiled artifact evidence without disclosure. */
#[Api(ApiStatus::Experimental)]
final class InvalidRuntimeArtifactException extends ViewRuntimeException
{
    private const array VIOLATIONS = ['invalid_root', 'invalid_directory', 'unsafe_path', 'wrong_directory_name', 'missing_artifact_file', 'artifact_file_type', 'artifact_file_bound', 'artifact_file_unreadable', 'artifact_file_unstable', 'metadata_framing', 'metadata_json', 'metadata_shape', 'schema_or_version', 'builder_contract', 'digest_grammar', 'model_digest_relation', 'generated_class_relation', 'generated_digest_relation', 'generated_framing', 'generated_syntax'];

    public static function invalid(string $violation, ?int $limit = null, ?int $observedCount = null, ?Throwable $previous = null): self
    {
        $context = ['violation' => in_array($violation, self::VIOLATIONS, true) ? $violation : 'unsupported_violation'];
        if (null !== $limit) {
            $context['limit'] = max(0, $limit);
        }
        if (null !== $observedCount) {
            $context['observed_count'] = max(0, $observedCount);
        }

        return new self(ViewRuntimeDiagnostic::InvalidArtifact, $context, $previous);
    }
}
