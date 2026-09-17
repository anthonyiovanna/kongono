<?php

declare(strict_types=1);

namespace Kongono\CacheBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheBuild\Diagnostic\CacheBuildDiagnostic;
use Throwable;

/** Reports one invalid canonical cache-profile artifact relation. */
#[Api(ApiStatus::Experimental)]
final class InvalidCacheProfileArtifactException extends CacheBuildException
{
    public static function invalid(string $violation, ?Throwable $previous = null, int $artifactByteCount = 0): self
    {
        $safe = in_array($violation, ['build_identity', 'artifact_identity', 'build_result_relation', 'artifact_framing', 'artifact_schema', 'artifact_canonical', 'artifact_identity_relation', 'profile_count_relation', 'profile_order', 'artifact_bound'], true) ? $violation : 'invalid_artifact';
        $context = ['violation' => $safe];
        if ($artifactByteCount > 0) {
            $context['artifact_byte_count'] = $artifactByteCount;
        }

        return new self(CacheBuildDiagnostic::InvalidArtifact, $context, $previous);
    }
}
