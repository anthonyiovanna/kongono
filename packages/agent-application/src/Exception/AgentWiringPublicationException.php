<?php

declare(strict_types=1);

namespace Kongono\AgentApplication\Exception;

use Kongono\AgentApplication\Diagnostic\AgentApplicationDiagnostic;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Preserves one publication failure without disclosing a filesystem location. */
#[Api(ApiStatus::Experimental)]
final class AgentWiringPublicationException extends AgentApplicationException
{
    public static function failed(string $stage, ?Throwable $previous = null): self
    {
        $allowed = ['path', 'temporary_create', 'temporary_write', 'temporary_sync', 'publish', 'existing_target', 'temporary_cleanup'];

        return new self(AgentApplicationDiagnostic::PublicationFailed, ['stage' => in_array($stage, $allowed, true) ? $stage : 'unsupported_stage'], $previous);
    }
}
