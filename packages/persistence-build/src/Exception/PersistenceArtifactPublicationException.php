<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\PersistenceBuild\Diagnostic\PersistenceBuildDiagnostic;
use Kongono\PersistenceBuild\Internal\PersistenceBuildFailureCategory;
use Throwable;

/** Reports a failed guarded content-addressed artifact publication. */
#[Api(ApiStatus::Experimental)]
final class PersistenceArtifactPublicationException extends PersistenceBuildException
{
    public static function failed(?Throwable $previous = null): self
    {
        return new self(PersistenceBuildDiagnostic::PublicationFailed, PersistenceBuildFailureCategory::Publication, true, $previous);
    }
}
