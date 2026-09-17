<?php

declare(strict_types=1);

namespace Kongono\CacheBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheBuild\Diagnostic\CacheBuildDiagnostic;
use Throwable;

/** Reports contained publication failure without disclosing paths or bytes. */
#[Api(ApiStatus::Experimental)]
final class CacheProfilePublicationException extends CacheBuildException
{
    public static function failed(string $violation, ?Throwable $previous = null, string $cleanupStatus = 'not_required'): self
    {
        $safe = in_array($violation, ['invalid_artifact', 'invalid_publication_root', 'unsafe_publication_component', 'temporary_create_failed', 'temporary_write_failed', 'temporary_sync_failed', 'path_changed', 'atomic_publish_failed', 'unsafe_existing_target', 'existing_target_unreadable', 'existing_target_changed', 'existing_target_conflict', 'temporary_cleanup_failed'], true) ? $violation : 'publication_failed';
        $cleanup = in_array($cleanupStatus, ['not_required', 'complete', 'failed'], true) ? $cleanupStatus : 'failed';

        return new self(CacheBuildDiagnostic::PublicationFailed, ['violation' => $safe, 'cleanup_status' => $cleanup], $previous);
    }
}
