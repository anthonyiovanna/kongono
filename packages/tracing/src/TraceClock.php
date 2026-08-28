<?php

declare(strict_types=1);

namespace Kongono\Tracing;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Supplies caller-owned monotonic nanosecond ticks without wall-clock meaning. */
#[Api(ApiStatus::Experimental)]
interface TraceClock
{
    public function nowNanoseconds(): int;
}
