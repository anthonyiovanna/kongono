<?php

declare(strict_types=1);

namespace Kongono\AssetBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetBuild\Diagnostic\AssetBuildDiagnostic;
use Throwable;

/** Reports byte-inexact currentness against one explicit complete source set. */
#[Api(ApiStatus::Experimental)]
final class StaleAssetBuildArtifactException extends AssetBuildException
{
    public static function stale(?Throwable $previous = null): self
    {
        return new self(AssetBuildDiagnostic::StaleArtifact, ['violation' => 'currentness_mismatch'], $previous);
    }
}
