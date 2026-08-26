<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\PersistenceBuild\Diagnostic\PersistenceBuildDiagnostic;
use Kongono\PersistenceBuild\Internal\PersistenceBuildFailureCategory;
use Throwable;

/** Reports a fixed non-disclosing exact-current generated persistence load failure. */
#[Api(ApiStatus::Experimental)]
final class GeneratedPersistenceLoadException extends PersistenceBuildException
{
    public static function failed(?Throwable $previous = null): self
    {
        return new self(
            PersistenceBuildDiagnostic::GeneratedPersistenceLoadFailed,
            PersistenceBuildFailureCategory::GeneratedPersistenceLoad,
            true,
            $previous,
        );
    }
}
