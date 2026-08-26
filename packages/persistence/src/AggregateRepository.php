<?php

declare(strict_types=1);

namespace Kongono\Persistence;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Exposes aggregate repository metadata; generated classes add named operations. */
#[Api(ApiStatus::Experimental)]
interface AggregateRepository
{
    public function aggregateName(): string;

    public function rootType(): string;

    /** @return list<AggregateLoadPlan> */
    public function loadPlans(): array;

    public function cascadePlan(): AggregateCascadePlan;
}
