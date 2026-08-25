<?php

declare(strict_types=1);

namespace Kongono\Database;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Closed provider capability vocabulary reported by an opened session. */
#[Api(ApiStatus::Experimental)]
enum DatabaseCapability
{
    case Transactions;
    case ReadOnlyTransactions;
    case IsolationReadCommitted;
    case IsolationRepeatableRead;
    case IsolationSerializable;
    case AffectedRows;
}
