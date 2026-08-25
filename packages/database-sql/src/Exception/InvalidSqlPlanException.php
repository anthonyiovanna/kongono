<?php

declare(strict_types=1);

namespace Kongono\DatabaseSql\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseSql\Diagnostic\SqlDiagnostic;

/** Reports invalid structure without copying rejected input. */
#[Api(ApiStatus::Experimental)]
final class InvalidSqlPlanException extends SqlException
{
    public static function validation(): self
    {
        return new self(SqlDiagnostic::InvalidPlan);
    }
}
