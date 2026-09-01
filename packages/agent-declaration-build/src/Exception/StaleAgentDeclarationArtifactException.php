<?php

declare(strict_types=1);

namespace Kongono\AgentDeclarationBuild\Exception;

use Kongono\AgentDeclarationBuild\Diagnostic\AgentDeclarationBuildDiagnostic;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Reports mismatch with the explicit caller-supplied current declaration. */
#[Api(ApiStatus::Experimental)]
final class StaleAgentDeclarationArtifactException extends AgentDeclarationBuildException
{
    public static function currentnessMismatch(): self
    {
        return new self(AgentDeclarationBuildDiagnostic::StaleArtifact, ['violation' => 'currentness_mismatch']);
    }
}
