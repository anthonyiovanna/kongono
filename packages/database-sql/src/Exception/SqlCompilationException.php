<?php

declare(strict_types=1);

namespace Kongono\DatabaseSql\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseSql\Diagnostic\SqlDiagnostic;
use Kongono\DatabaseSql\SqlPlanKind;

/** Reports deterministic dialect compilation rejection without emitted SQL. */
#[Api(ApiStatus::Experimental)]
final class SqlCompilationException extends SqlException
{
    public static function rejected(SqlPlanKind $planKind, string $profile): self
    {
        return new self(SqlDiagnostic::Compilation, $planKind, $profile);
    }

    public static function parameterOverflow(SqlPlanKind $planKind, string $profile, int $count): self
    {
        return new self(SqlDiagnostic::Compilation, $planKind, $profile, parameterCount: $count);
    }
}
