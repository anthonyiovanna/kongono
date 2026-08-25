<?php

declare(strict_types=1);

namespace Kongono\DatabaseSql;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Database\DatabaseStatement;

/** Declares one explicit dialect profile and compiles accepted plans without execution. */
#[Api(ApiStatus::Experimental)]
interface SqlDialectCompiler
{
    public function capabilities(): SqlDialectCapabilities;

    public function compile(SqlPlan $plan): DatabaseStatement;
}
