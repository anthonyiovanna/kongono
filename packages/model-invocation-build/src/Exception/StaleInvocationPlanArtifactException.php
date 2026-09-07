<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationBuild\Diagnostic\ModelInvocationBuildDiagnostic;

/** Reports mismatch with the one explicit current invocation-plan source. */
#[Api(ApiStatus::Experimental)]
final class StaleInvocationPlanArtifactException extends ModelInvocationBuildException
{
    public static function currentnessMismatch(): self
    {
        return new self(ModelInvocationBuildDiagnostic::StaleArtifact, ['violation' => 'currentness_mismatch']);
    }
}
