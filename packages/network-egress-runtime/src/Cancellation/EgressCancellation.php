<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressRuntime\Cancellation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Reports caller-owned cancellation state at explicit checkpoints. */
#[Api(ApiStatus::Experimental)]
interface EgressCancellation
{
    public function isCancellationRequested(): bool;
}
