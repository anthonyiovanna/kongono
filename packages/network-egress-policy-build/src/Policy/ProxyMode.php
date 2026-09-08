<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressPolicyBuild\Policy;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Declares inert proxy posture without discovering or selecting a proxy. */
#[Api(ApiStatus::Experimental)]
enum ProxyMode: string
{
    case Deny = 'deny';
    case ExplicitOnly = 'explicit_only';
}
