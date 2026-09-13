<?php

declare(strict_types=1);

namespace Kongono\ViewBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewBuild\Diagnostic\ViewBuildDiagnostic;
use Throwable;

/** Reports mismatch against one explicitly supplied current source set. */
#[Api(ApiStatus::Experimental)]
final class StaleCompiledViewArtifactException extends ViewBuildException
{
    public static function mismatch(?Throwable $previous = null): self
    {
        return new self(ViewBuildDiagnostic::StaleArtifact, ['violation' => 'currentness_mismatch'], $previous);
    }
}
