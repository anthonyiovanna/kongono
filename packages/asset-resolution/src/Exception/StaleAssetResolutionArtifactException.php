<?php

declare(strict_types=1);

namespace Kongono\AssetResolution\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetResolution\Diagnostic\AssetResolutionDiagnostic;
use Throwable;

/** Reports exact-current identity mismatch without returning stale metadata. */
#[Api(ApiStatus::Experimental)]
final class StaleAssetResolutionArtifactException extends AssetResolutionException
{
    public static function stale(?Throwable $previous = null): self
    {
        return new self(AssetResolutionDiagnostic::StaleIdentity, ['violation' => 'artifact_identity_mismatch'], $previous);
    }
}
