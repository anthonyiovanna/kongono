<?php

declare(strict_types=1);

namespace Kongono\Database;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Executes one immutable parameter-bound statement without interpreting its SQL. */
#[Api(ApiStatus::Public)]
interface DatabaseExecutor
{
    public function execute(DatabaseStatement $statement): RowStream|CommandResult;
}
