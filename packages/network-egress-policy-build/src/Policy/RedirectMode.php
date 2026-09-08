<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressPolicyBuild\Policy;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Declares inert redirect posture without following a redirect. */
#[Api(ApiStatus::Experimental)]
enum RedirectMode: string
{
    case Deny = 'deny';
    case SameOrigin = 'same_origin';
}
