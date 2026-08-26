<?php

declare(strict_types=1);

namespace Kongono\Persistence;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Database\DatabaseExecutor;

/** Executes one explicit aggregate load without acquiring or retaining authority. */
#[Api(ApiStatus::Experimental)]
interface AggregateLoadExecutor
{
    public function load(DatabaseExecutor $database, AggregateLoadPlan $plan, EntityIdentity $identity): ?LoadedAggregate;
}
