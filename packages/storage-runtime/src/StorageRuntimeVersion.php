<?php

declare(strict_types=1);

namespace Kongono\StorageRuntime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the only accepted storage-runtime contract version. */
#[Api(ApiStatus::Experimental)]
enum StorageRuntimeVersion: int
{
    case Version1 = 1;
}
