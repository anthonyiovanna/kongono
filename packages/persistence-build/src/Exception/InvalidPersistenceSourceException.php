<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\PersistenceBuild\Diagnostic\PersistenceBuildDiagnostic;
use Kongono\PersistenceBuild\Internal\PersistenceBuildFailureCategory;

/** Reports a rejected source mapping domain aggregate or relationship contract. */
#[Api(ApiStatus::Experimental)]
final class InvalidPersistenceSourceException extends PersistenceBuildException
{
    public static function invalid(PersistenceBuildDiagnostic $diagnostic, PersistenceBuildFailureCategory $category): self
    {
        return new self($diagnostic, $category);
    }
}
