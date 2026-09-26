<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Condition;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the only supported mutation preconditions. */
#[Api(ApiStatus::Experimental)]
enum StorageMutationConditionKind: string
{
    case IfAbsent = 'if_absent';
    case IfVersion = 'if_version';
}
