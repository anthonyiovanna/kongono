<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceResolverSystem\Profile;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Selects which accepted address family is ordered first. */
#[Api(ApiStatus::Experimental)]
enum AddressFamilyPreference: string
{
    case Ipv4First = 'ipv4_first';
    case Ipv6First = 'ipv6_first';
}
