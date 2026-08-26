<?php

declare(strict_types=1);

namespace Kongono\Persistence;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Describes an entity tracked by one persistence context. */
#[Api(ApiStatus::Experimental)]
enum EntityState
{
    case New;
    case Managed;
    case Removed;
}
