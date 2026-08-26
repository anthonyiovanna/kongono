<?php

declare(strict_types=1);

namespace Kongono\Persistence\Tests\Fixture;

use Kongono\Database\DatabaseExecutor;
use Kongono\Persistence\AggregateCascadeExecutor;
use Kongono\Persistence\AggregateCascadePlan;
use Kongono\Persistence\CascadeAction;
use Kongono\Persistence\FlushResult;
use Kongono\Persistence\LoadedAggregate;

final class RecordingAggregateCascadeExecutor implements AggregateCascadeExecutor
{
    public int $calls = 0;
    public ?DatabaseExecutor $database = null;
    public ?AggregateCascadePlan $plan = null;
    public ?CascadeAction $action = null;
    public ?LoadedAggregate $aggregate = null;

    public function __construct(public FlushResult $result) {}

    public function execute(DatabaseExecutor $database, AggregateCascadePlan $plan, CascadeAction $action, LoadedAggregate $aggregate): FlushResult
    {
        ++$this->calls;
        $this->database = $database;
        $this->plan = $plan;
        $this->action = $action;
        $this->aggregate = $aggregate;

        return $this->result;
    }
}
