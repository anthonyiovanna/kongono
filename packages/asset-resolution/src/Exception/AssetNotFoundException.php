<?php

declare(strict_types=1);

namespace Kongono\AssetResolution\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetResolution\Diagnostic\AssetResolutionDiagnostic;
use Throwable;

/** Reports a missing logical identity without disclosing its value. */
#[Api(ApiStatus::Experimental)]
final class AssetNotFoundException extends AssetResolutionException
{
    public static function missing(?Throwable $previous = null): self
    {
        return new self(AssetResolutionDiagnostic::MissingIdentity, ['violation' => 'logical_identity_absent'], $previous);
    }
}
