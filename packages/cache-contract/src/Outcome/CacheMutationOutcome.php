<?php

declare(strict_types=1);

namespace Kongono\CacheContract\Outcome;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Names the closed mutation outcome vocabulary without mutating state. */
#[Api(ApiStatus::Experimental)]
enum CacheMutationOutcome: string
{
    case Stored = 'stored';
    case Removed = 'removed';
    case NotFound = 'not_found';
    case Conflict = 'conflict';
    case Rejected = 'rejected';
    case Unavailable = 'unavailable';
}
