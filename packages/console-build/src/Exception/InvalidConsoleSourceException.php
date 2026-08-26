<?php

declare(strict_types=1);

namespace Kongono\ConsoleBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ConsoleBuild\Diagnostic\ConsoleBuildDiagnostic;
use Kongono\ConsoleBuild\Internal\ConsoleBuildFailureCategory;

/** Reports rejected command source or model evidence without retaining it. */
#[Api(ApiStatus::Experimental)]
final class InvalidConsoleSourceException extends ConsoleBuildException
{
    public static function invalid(ConsoleBuildDiagnostic $diagnostic, ConsoleBuildFailureCategory $category): self
    {
        return new self($diagnostic, $category);
    }
}
