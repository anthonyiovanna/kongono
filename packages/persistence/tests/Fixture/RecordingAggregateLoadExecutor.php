<?php

declare(strict_types=1);

namespace Kongono\Persistence\Tests\Fixture;

use Kongono\Database\DatabaseExecutor;
use Kongono\Persistence\AggregateLoadExecutor;
use Kongono\Persistence\AggregateLoadPlan;
use Kongono\Persistence\EntityIdentity;
use Kongono\Persistence\LoadedAggregate;

final class RecordingAggregateLoadExecutor implements AggregateLoadExecutor
{
    public int $calls = 0;
    public ?DatabaseExecutor $database = null;
    public ?AggregateLoadPlan $plan = null;
    public ?EntityIdentity $identity = null;

    public function __construct(public ?LoadedAggregate $result) {}

    public function load(DatabaseExecutor $database, AggregateLoadPlan $plan, EntityIdentity $identity): ?LoadedAggregate
    {
        ++$this->calls;
        $this->database = $database;
        $this->plan = $plan;
        $this->identity = $identity;

        return $this->result;
    }
}
