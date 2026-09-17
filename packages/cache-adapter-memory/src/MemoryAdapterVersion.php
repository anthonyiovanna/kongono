<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterMemory;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the exact bounded-memory implementation version. */
#[Api(ApiStatus::Experimental)]
enum MemoryAdapterVersion: int
{
    case Version1 = 1;
}
