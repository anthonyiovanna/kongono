<?php

declare(strict_types=1);

namespace Kongono\ViewApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewApplication\Diagnostic\ViewApplicationDiagnostic;
use Throwable;

/** Reports exact-current authentication or load failure. */
#[Api(ApiStatus::Internal)]
final class ViewApplicationLoadException extends ViewApplicationException
{
    public static function failed(string $stage, ?Throwable $previous = null): self
    {
        return new self(ViewApplicationDiagnostic::LoadFailed, 'artifact_load' === $stage ? $stage : 'profile_validation', $previous);
    }
}
