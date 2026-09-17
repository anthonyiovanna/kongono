<?php

declare(strict_types=1);

namespace Kongono\CacheRuntime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the exact cache-runtime implementation contract. */
#[Api(ApiStatus::Experimental)]
enum CacheRuntimeVersion: int
{
    case Version1 = 1;
}
