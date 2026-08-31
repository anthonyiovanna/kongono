<?php

declare(strict_types=1);

namespace Kongono\ExtensionBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionBuild\Diagnostic\ExtensionBuildDiagnostic;

/** Reports mismatch with caller-supplied current extension declarations. */
#[Api(ApiStatus::Experimental)]
final class StaleExtensionDeclarationArtifactException extends ExtensionBuildException
{
    public static function currentnessMismatch(): self
    {
        return new self(ExtensionBuildDiagnostic::StaleArtifact, [
            'violation' => 'currentness_mismatch',
            'kind' => 'declaration',
        ]);
    }
}
