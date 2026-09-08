<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressApplication\Diagnostic\NetworkEgressApplicationDiagnostic;
use Throwable;

/** Preserves publication failure identity without disclosing a path. */
#[Api(ApiStatus::Experimental)]
final class NetworkEgressWiringPublicationException extends NetworkEgressApplicationException
{
    public static function failed(string $stage, ?Throwable $previous = null): self
    {
        $allowed = ['path', 'temporary_create', 'temporary_write', 'temporary_sync', 'publish', 'existing_target', 'temporary_cleanup'];

        return new self(NetworkEgressApplicationDiagnostic::PublicationFailed, ['stage' => in_array($stage, $allowed, true) ? $stage : 'unsupported_stage'], $previous);
    }
}
