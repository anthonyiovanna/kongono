<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceBindingBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceBindingBuild\Diagnostic\ExternalServiceBindingBuildDiagnostic;

/** Distinguishes valid artifact evidence from explicit-source currentness. */
#[Api(ApiStatus::Experimental)]
final class StaleExternalServiceBindingArtifactException extends ExternalServiceBindingBuildException
{
    public static function currentnessMismatch(): self
    {
        return new self(ExternalServiceBindingBuildDiagnostic::StaleArtifact, ['violation' => 'current_source_mismatch']);
    }
}
