<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressRuntime\Address;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Names the binary address family retained by a resolved address. */
#[Api(ApiStatus::Experimental)]
enum AddressFamily: string
{
    case Ipv4 = 'ipv4';
    case Ipv6 = 'ipv6';
}
