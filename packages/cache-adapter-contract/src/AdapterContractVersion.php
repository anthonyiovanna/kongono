<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterContract;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the exact neutral cache-adapter protocol version. */
#[Api(ApiStatus::Experimental)]
enum AdapterContractVersion: int
{
    case Version1 = 1;
}
