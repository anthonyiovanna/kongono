<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\PersistenceBuild\Diagnostic\PersistenceBuildDiagnostic;
use Kongono\PersistenceBuild\Internal\PersistenceBuildFailureCategory;

/** Reports structurally valid persistence evidence that is not exact-current. */
#[Api(ApiStatus::Experimental)]
final class StalePersistenceArtifactException extends PersistenceBuildException
{
    public static function currentMismatch(): self
    {
        return new self(PersistenceBuildDiagnostic::StaleArtifact, PersistenceBuildFailureCategory::CurrentState);
    }
}
