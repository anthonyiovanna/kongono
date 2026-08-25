<?php

declare(strict_types=1);

namespace Kongono\Database;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Closed transaction access-mode vocabulary. */
#[Api(ApiStatus::Experimental)]
enum TransactionAccess
{
    case ReadWrite;
    case ReadOnly;
}
