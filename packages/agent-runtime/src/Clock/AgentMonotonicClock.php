<?php

declare(strict_types=1);

namespace Kongono\AgentRuntime\Clock;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Supplies caller-owned monotonic milliseconds without consulting system time. */
#[Api(ApiStatus::Experimental)]
interface AgentMonotonicClock
{
    public function milliseconds(): int;
}
