<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterContract\Result;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Names the adapter-level lookup state without making freshness claims. */
#[Api(ApiStatus::Experimental)]
enum CacheAdapterLookupStatus: string
{
    case Found = 'found';
    case Miss = 'miss';
    case Rejected = 'rejected';
    case Unavailable = 'unavailable';
}
