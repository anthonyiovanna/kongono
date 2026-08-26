<?php

declare(strict_types=1);

namespace Kongono\ConsoleBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ConsoleBuild\Diagnostic\ConsoleBuildDiagnostic;
use Kongono\ConsoleBuild\Internal\ConsoleBuildFailureCategory;

/** Reports structurally valid command evidence that is not exact-current. */
#[Api(ApiStatus::Experimental)]
final class StaleConsoleArtifactException extends ConsoleBuildException
{
    public static function currentMismatch(): self
    {
        return new self(ConsoleBuildDiagnostic::StaleArtifact, ConsoleBuildFailureCategory::CurrentState);
    }
}
