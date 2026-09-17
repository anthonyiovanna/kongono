<?php

declare(strict_types=1);

namespace Kongono\CacheContract\Operation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Names an inert cache operation without performing it. */
#[Api(ApiStatus::Experimental)]
enum CacheOperation: string
{
    case Lookup = 'lookup';
    case Store = 'store';
    case Replace = 'replace';
    case Remove = 'remove';
}
