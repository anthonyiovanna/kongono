<?php

declare(strict_types=1);

namespace Kongono\CacheContract\Freshness;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Describes the exact closed freshness state at caller-supplied time. */
#[Api(ApiStatus::Experimental)]
enum CacheFreshness: string
{
    case Fresh = 'fresh';
    case Expired = 'expired';
}
