<?php

declare(strict_types=1);

namespace Kongono\DatabaseMigration;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Records the caller's explicit decision for source-declared destructive work. */
#[Api(ApiStatus::Experimental)]
enum DestructiveMigrationPolicy: string
{
    case Deny = 'deny';
    case Allow = 'allow';
}
