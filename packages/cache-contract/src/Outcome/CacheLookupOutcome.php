<?php

declare(strict_types=1);

namespace Kongono\CacheContract\Outcome;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Names the closed lookup outcome vocabulary without mapping a provider. */
#[Api(ApiStatus::Experimental)]
enum CacheLookupOutcome: string
{
    case FreshHit = 'fresh_hit';
    case Miss = 'miss';
    case Expired = 'expired';
    case Rejected = 'rejected';
    case Unavailable = 'unavailable';
}
