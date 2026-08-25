<?php

declare(strict_types=1);

namespace Kongono\Database;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Observable transaction lifecycle states, including uncertain provider failure. */
#[Api(ApiStatus::Experimental)]
enum TransactionState
{
    case Active;
    case Committed;
    case RolledBack;
    case Failed;
}
