<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationRuntime\Clock;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Caller-owned nonnegative monotonic millisecond source. */
#[Api(ApiStatus::Experimental)]
interface InvocationMonotonicClock
{
    public function milliseconds(): int;
}
