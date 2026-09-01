<?php

declare(strict_types=1);

namespace Kongono\AgentDeclarationBuild\Exception;

use Kongono\AgentDeclarationBuild\Diagnostic\AgentDeclarationBuildDiagnostic;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Reports one bounded guarded no-clobber publication failure. */
#[Api(ApiStatus::Experimental)]
final class AgentDeclarationPublicationException extends AgentDeclarationBuildException
{
    public static function failed(string $violation, ?Throwable $previous = null): self
    {
        $allowed = [
            'ambiguous_path', 'artifact_identity_mismatch', 'atomic_publish_failed',
            'existing_target_changed', 'existing_target_conflict', 'existing_target_invalid',
            'existing_target_unreadable', 'invalid_artifact', 'invalid_output_directory',
            'link_or_reparse', 'noncanonical_path', 'temporary_cleanup_failed',
            'temporary_create_failed', 'temporary_open_failed', 'temporary_sync_failed',
            'temporary_write_failed', 'unsafe_existing_target', 'unsafe_temporary_file',
        ];

        return new self(AgentDeclarationBuildDiagnostic::PublicationFailed, [
            'violation' => in_array($violation, $allowed, true) ? $violation : 'unsupported_violation',
        ], $previous);
    }
}
