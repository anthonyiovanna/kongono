<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Capability;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies one closed neutral storage support capability. */
#[Api(ApiStatus::Experimental)]
enum StorageCapability: string
{
    case Read = 'read';
    case Create = 'create';
    case Replace = 'replace';
    case Delete = 'delete';
    case List = 'list';
    case Metadata = 'metadata';
    case ContentDigest = 'content_digest';
    case ConditionalMutation = 'conditional_mutation';
}
