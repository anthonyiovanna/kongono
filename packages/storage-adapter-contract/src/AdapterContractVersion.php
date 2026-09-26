<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterContract;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the closed neutral storage-adapter protocol version. */
#[Api(ApiStatus::Experimental)]
enum AdapterContractVersion: int
{
    case Version1 = 1;
}
