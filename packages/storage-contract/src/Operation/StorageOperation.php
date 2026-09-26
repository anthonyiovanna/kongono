<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Operation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies one closed neutral storage operation. */
#[Api(ApiStatus::Experimental)]
enum StorageOperation: string
{
    case Read = 'read';
    case Create = 'create';
    case Replace = 'replace';
    case Delete = 'delete';
    case List = 'list';
}
