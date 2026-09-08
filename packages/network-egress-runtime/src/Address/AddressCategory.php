<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressRuntime\Address;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Names the conservative admission category assigned to an address. */
#[Api(ApiStatus::Experimental)]
enum AddressCategory: string
{
    case Public = 'public';
    case Private = 'private';
    case Loopback = 'loopback';
    case LinkLocal = 'link_local';
    case Multicast = 'multicast';
    case Unspecified = 'unspecified';
    case Documentation = 'documentation';
    case Reserved = 'reserved';
}
