<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationRuntime\Adapter;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationBuild\Plan\AdapterIdentity;
use Kongono\ModelInvocationBuild\Plan\InvocationPlanSource;

/** Application-owned adapter selected directly for one exact invocation plan. */
#[Api(ApiStatus::Experimental)]
interface InvocationAdapter
{
    public function identity(): AdapterIdentity;

    public function open(InvocationPlanSource $source): InvocationAdapterSession;
}
