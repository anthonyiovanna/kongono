<?php

declare(strict_types=1);

namespace Kongono\AssetApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetApplication\Diagnostic\AssetApplicationDiagnostic;
use Throwable;

/** Reports an exact-current resolution failure without rejected-value disclosure. */
#[Api(ApiStatus::Internal)]
final class AssetResolutionOperationException extends AssetApplicationException
{
    public static function failed(string $stage, ?Throwable $previous = null): self
    {
        return new self(AssetApplicationDiagnostic::ResolutionFailed, in_array($stage, ['current_check', 'artifact', 'lookup'], true) ? $stage : 'resolution', $previous);
    }
}
