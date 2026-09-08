<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressRuntime\Clock;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Supplies explicit monotonic millisecond observations. */
#[Api(ApiStatus::Experimental)]
interface EgressMonotonicClock
{
    public function milliseconds(): int;
}
