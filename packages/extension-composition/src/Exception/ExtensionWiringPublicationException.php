<?php

declare(strict_types=1);

namespace Kongono\ExtensionComposition\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Reports atomic no-clobber publication failure without disclosing paths or native messages. */
#[Api(ApiStatus::Experimental)]
final class ExtensionWiringPublicationException extends ExtensionCompositionException
{
    public static function failed(string $kind, ?Throwable $previous = null): self
    {
        $allowed = ['invalid_artifact', 'artifact_identity_mismatch', 'invalid_output_directory', 'temporary_create_failed', 'unsafe_temporary_file', 'temporary_open_failed', 'temporary_write_failed', 'temporary_sync_failed', 'directory_changed', 'atomic_publish_failed', 'temporary_cleanup_failed', 'unsafe_existing_target', 'existing_target_unreadable', 'existing_target_changed', 'existing_target_conflict', 'existing_target_invalid'];

        return new self(['violation' => in_array($kind, $allowed, true) ? $kind : 'unsupported_violation'], $previous);
    }
}
