<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationApplication\Diagnostic\ModelInvocationApplicationDiagnostic;
use Throwable;

/** Preserves publication failure identity without disclosing a path. */
#[Api(ApiStatus::Experimental)]
final class ModelInvocationWiringPublicationException extends ModelInvocationApplicationException
{
    public static function failed(string $stage, ?Throwable $previous = null): self
    {
        $allowed = ['path', 'temporary_create', 'temporary_write', 'temporary_sync', 'publish', 'existing_target', 'temporary_cleanup'];

        return new self(ModelInvocationApplicationDiagnostic::PublicationFailed, ['stage' => in_array($stage, $allowed, true) ? $stage : 'unsupported_stage'], $previous);
    }
}
