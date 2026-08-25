<?php

declare(strict_types=1);

namespace Kongono\DatabaseSql\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseSql\Diagnostic\SqlDiagnostic;
use Kongono\DatabaseSql\SqlDialectCapability;
use Kongono\DatabaseSql\SqlPlanKind;

/** Reports an unsupported plan or required capability without plan data. */
#[Api(ApiStatus::Experimental)]
final class UnsupportedDialectCapabilityException extends SqlException
{
    public static function capability(SqlPlanKind $planKind, SqlDialectCapability $capability, string $profile): self
    {
        return new self(SqlDiagnostic::UnsupportedDialectCapability, $planKind, $profile, $capability);
    }

    public static function foreignPlan(string $profile): self
    {
        return new self(SqlDiagnostic::UnsupportedDialectCapability, dialectProfile: $profile);
    }
}
