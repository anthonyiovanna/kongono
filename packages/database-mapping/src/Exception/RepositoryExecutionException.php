<?php

declare(strict_types=1);

namespace Kongono\DatabaseMapping\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseMapping\Diagnostic\MappingDiagnostic;
use Kongono\DatabaseMapping\Internal\MappingFailureCategory;

/** Reports an executor result that violates the generated operation's neutral contract. */
#[Api(ApiStatus::Public)]
final class RepositoryExecutionException extends MappingException
{
    public static function invalidFindResult(): self
    {
        return new self(MappingDiagnostic::RepositoryExecutionFailed, MappingFailureCategory::ResultShape, artifactCurrent: true, operationKind: 'find');
    }

    public static function invalidInsertResult(): self
    {
        return new self(MappingDiagnostic::RepositoryExecutionFailed, MappingFailureCategory::ResultShape, artifactCurrent: true, operationKind: 'insert');
    }

    public static function invalidUpdateResult(): self
    {
        return new self(MappingDiagnostic::RepositoryExecutionFailed, MappingFailureCategory::ResultShape, artifactCurrent: true, operationKind: 'update');
    }

    public static function invalidDeleteResult(): self
    {
        return new self(MappingDiagnostic::RepositoryExecutionFailed, MappingFailureCategory::ResultShape, artifactCurrent: true, operationKind: 'delete');
    }
}
