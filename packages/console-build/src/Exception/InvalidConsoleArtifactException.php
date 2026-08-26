<?php

declare(strict_types=1);

namespace Kongono\ConsoleBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ConsoleBuild\Diagnostic\ConsoleBuildDiagnostic;
use Kongono\ConsoleBuild\Internal\ConsoleBuildFailureCategory;

/** Reports malformed noncanonical partial mixed or tampered command evidence. */
#[Api(ApiStatus::Experimental)]
final class InvalidConsoleArtifactException extends ConsoleBuildException
{
    public static function invalid(ConsoleBuildFailureCategory $category): self
    {
        return new self(ConsoleBuildDiagnostic::InvalidArtifact, $category);
    }
}
