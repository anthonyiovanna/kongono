<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceBindingBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceBindingBuild\Diagnostic\ExternalServiceBindingBuildDiagnostic;

/** Distinguishes structurally valid generated PHP from explicit current evidence. */
#[Api(ApiStatus::Experimental)]
final class StaleGeneratedExternalServiceBindingsException extends ExternalServiceBindingBuildException
{
    public static function currentnessMismatch(): self
    {
        return new self(ExternalServiceBindingBuildDiagnostic::StaleGeneratedPhp, ['violation' => 'current_source_or_artifact_mismatch']);
    }
}
