<?php

declare(strict_types=1);

namespace Kongono\IdentityAccess;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Names the closed neutral kinds of principal without granting authority. */
#[Api(ApiStatus::Experimental)]
enum PrincipalKind: string
{
    case Human = 'human';
    case Service = 'service';
    case Organization = 'organization';
    case System = 'system';
    case Agent = 'agent';
}
