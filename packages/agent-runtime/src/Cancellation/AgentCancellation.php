<?php

declare(strict_types=1);

namespace Kongono\AgentRuntime\Cancellation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Supplies one operation-scoped cancellation observation without ambient state. */
#[Api(ApiStatus::Experimental)]
interface AgentCancellation
{
    public function isCancellationRequested(): bool;
}
