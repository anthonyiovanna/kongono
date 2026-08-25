<?php

declare(strict_types=1);

namespace Kongono\DatabaseMapping\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseMapping\Diagnostic\MappingDiagnostic;
use Kongono\DatabaseMapping\Internal\MappingFailureCategory;
use Throwable;

/** Reports rejection at the exact-current generated data-access load boundary. */
#[Api(ApiStatus::Public)]
final class GeneratedDataAccessLoadException extends MappingException
{
    public static function failed(?Throwable $previous = null): self
    {
        return new self(
            MappingDiagnostic::InvalidGeneratedDataAccess,
            MappingFailureCategory::GeneratedLoad,
            artifactCurrent: false,
            operationKind: 'load',
            previous: $previous,
        );
    }
}
