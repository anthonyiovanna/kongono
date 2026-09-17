<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterMemory\Configuration;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Names the sole deterministic memory-adapter eviction policy. */
#[Api(ApiStatus::Experimental)]
enum MemoryEvictionPolicy: string
{
    case OldestInsertionFirst = 'oldest_insertion_first';
}
