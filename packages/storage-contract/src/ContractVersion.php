<?php

declare(strict_types=1);

namespace Kongono\StorageContract;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the exact neutral storage-contract version. */
#[Api(ApiStatus::Experimental)]
enum ContractVersion: int
{
    case Version1 = 1;
}
