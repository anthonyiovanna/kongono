<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressRuntime\Resolver;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressContract\Origin\CanonicalOrigin;

/** Resolves one origin through a directly supplied caller-owned capability. */
#[Api(ApiStatus::Experimental)]
interface EgressResolver
{
    public function resolve(CanonicalOrigin $origin): EgressResolutionResult;
}
