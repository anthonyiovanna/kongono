<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\PersistenceBuild\Diagnostic\PersistenceBuildDiagnostic;
use Kongono\PersistenceBuild\Internal\PersistenceBuildFailureCategory;

/** Reports malformed noncanonical partial mixed or tampered artifact bytes. */
#[Api(ApiStatus::Experimental)]
final class InvalidPersistenceArtifactException extends PersistenceBuildException
{
    public static function invalid(PersistenceBuildFailureCategory $category): self
    {
        return new self(PersistenceBuildDiagnostic::InvalidArtifact, $category);
    }
}
