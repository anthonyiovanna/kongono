<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceBindingBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceBindingBuild\Diagnostic\ExternalServiceBindingBuildDiagnostic;
use Throwable;

/** Reports one bounded guarded no-clobber generated PHP publication failure. */
#[Api(ApiStatus::Experimental)]
final class GeneratedExternalServiceBindingsPublicationException extends ExternalServiceBindingBuildException
{
    public static function failed(string $violation, ?Throwable $previous = null): self
    {
        $allowed = ['invalid_generated_php', 'generated_identity_mismatch', 'invalid_output_directory', 'unsafe_existing_target', 'existing_target_unreadable', 'existing_target_changed', 'existing_target_conflict', 'existing_target_invalid', 'temporary_create_failed', 'temporary_open_failed', 'unsafe_temporary_file', 'temporary_write_failed', 'temporary_sync_failed', 'atomic_publish_failed', 'temporary_cleanup_failed', 'path_changed'];

        return new self(ExternalServiceBindingBuildDiagnostic::GeneratedPhpPublicationFailed, ['violation' => in_array($violation, $allowed, true) ? $violation : 'unsupported_violation'], $previous);
    }
}
