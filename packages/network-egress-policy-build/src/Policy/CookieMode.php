<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressPolicyBuild\Policy;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Declares inert cookie posture without loading or retaining cookie material. */
#[Api(ApiStatus::Experimental)]
enum CookieMode: string
{
    case Deny = 'deny';
    case ExplicitOnly = 'explicit_only';
}
