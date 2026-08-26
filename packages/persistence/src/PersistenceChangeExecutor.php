<?php

declare(strict_types=1);

namespace Kongono\Persistence;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Database\DatabaseExecutor;

/** Translates one reviewed change through one caller-authorized database boundary. */
#[Api(ApiStatus::Experimental)]
interface PersistenceChangeExecutor
{
    public function execute(DatabaseExecutor $database, PersistenceChange $change): ChangeExecutionOutcome;
}
