<?php

declare(strict_types=1);

namespace Kongono\Persistence;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Database\DatabaseExecutor;

/** Executes one explicit one-level cascade without transaction or retry authority. */
#[Api(ApiStatus::Experimental)]
interface AggregateCascadeExecutor
{
    public function execute(DatabaseExecutor $database, AggregateCascadePlan $plan, CascadeAction $action, LoadedAggregate $aggregate): FlushResult;
}
