<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationRuntime\Cancellation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Caller-owned cancellation probe checked at fixed synchronous boundaries. */
#[Api(ApiStatus::Experimental)]
interface InvocationCancellation
{
    public function isCancellationRequested(): bool;
}
