<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressPolicyBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressPolicyBuild\Diagnostic\NetworkEgressPolicyBuildDiagnostic;

/** Reports mismatch with the one explicit current typed policy source. */
#[Api(ApiStatus::Experimental)]
final class StaleEgressPolicyArtifactException extends NetworkEgressPolicyBuildException
{
    public static function currentnessMismatch(): self
    {
        return new self(NetworkEgressPolicyBuildDiagnostic::StalePolicyArtifact, ['violation' => 'currentness_mismatch']);
    }
}
