<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceCredentialRuntime\Clock;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Supplies caller-owned monotonic millisecond observations for one lease operation. */
#[Api(ApiStatus::Experimental)]
interface CredentialMonotonicClock
{
    public function milliseconds(): int;
}
