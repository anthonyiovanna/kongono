<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationApplication\Generated;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationApplication\Profile\ModelInvocationApplicationProfile;
use Kongono\ModelInvocationApplication\Runtime\ModelInvocationApplication;
use Kongono\ModelInvocationRuntime\Adapter\InvocationAdapter;

/** Fixed boundary implemented only by exact generated single-profile wiring. */
#[Api(ApiStatus::Experimental)]
interface GeneratedModelInvocationApplicationBoundary
{
    public function start(ModelInvocationApplicationProfile $profile, InvocationAdapter $adapter): ModelInvocationApplication;
}
