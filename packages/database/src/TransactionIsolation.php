<?php

declare(strict_types=1);

namespace Kongono\Database;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Closed version-1 transaction isolation vocabulary. */
#[Api(ApiStatus::Experimental)]
enum TransactionIsolation
{
    case Default;
    case ReadCommitted;
    case RepeatableRead;
    case Serializable;
}
