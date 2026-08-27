<?php

declare(strict_types=1);

namespace Kongono\IdentityAccess;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Names inert authorization decisions without evaluating policy or enforcement. */
#[Api(ApiStatus::Experimental)]
enum AuthorizationDecision: string
{
    case Permit = 'permit';
    case Deny = 'deny';
    case Indeterminate = 'indeterminate';
}
