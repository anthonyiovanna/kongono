<?php

declare(strict_types=1);

namespace Kongono\DatabaseMapping\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseMapping\Diagnostic\MappingDiagnostic;
use Kongono\DatabaseMapping\Internal\MappingFailureCategory;

/** Reports more than one row for a generated single-record lookup. */
#[Api(ApiStatus::Public)]
final class RowCardinalityException extends MappingException
{
    public static function invalid(int $count): self
    {
        return new self(MappingDiagnostic::RowCardinalityInvalid, MappingFailureCategory::Cardinality, count: $count, artifactCurrent: true, operationKind: 'find');
    }
}
