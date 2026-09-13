<?php

declare(strict_types=1);

namespace Kongono\ViewBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewBuild\Diagnostic\ViewBuildDiagnostic;
use Throwable;

/** Reports a bounded no-clobber publication failure. */
#[Api(ApiStatus::Experimental)]
final class ViewArtifactPublicationException extends ViewBuildException
{
    private const array VIOLATIONS = ['invalid_artifact', 'artifact_identity_mismatch', 'invalid_output_directory', 'unsafe_existing_target', 'existing_target_unreadable', 'existing_target_changed', 'existing_target_conflict', 'temporary_create_failed', 'temporary_write_failed', 'temporary_sync_failed', 'unsafe_temporary_directory', 'path_changed', 'atomic_publish_failed', 'temporary_cleanup_failed'];

    public static function failed(string $violation, ?Throwable $previous = null): self
    {
        return new self(ViewBuildDiagnostic::PublicationFailed, ['violation' => in_array($violation, self::VIOLATIONS, true) ? $violation : 'unsupported_violation'], $previous);
    }
}
