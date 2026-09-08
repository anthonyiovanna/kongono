<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressPolicyBuild\Policy;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Declares inert address-scope posture without classifying a live address. */
#[Api(ApiStatus::Experimental)]
enum AddressScope: string
{
    case PublicOnly = 'public_only';
    case ExplicitPrivateAllowed = 'explicit_private_allowed';
}
