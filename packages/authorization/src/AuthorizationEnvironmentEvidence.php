<?php

declare(strict_types=1);

namespace Kongono\Authorization;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Exposes only the profile of caller-owned opaque environmental evidence. */
#[Api(ApiStatus::Experimental)]
interface AuthorizationEnvironmentEvidence
{
    public function profile(): AuthorizationEnvironmentProfile;
}
