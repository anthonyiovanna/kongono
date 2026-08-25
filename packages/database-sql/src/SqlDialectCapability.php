<?php

declare(strict_types=1);

namespace Kongono\DatabaseSql;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Closed version-1 SQL dialect capability vocabulary. */
#[Api(ApiStatus::Experimental)]
enum SqlDialectCapability
{
    case Select;
    case Insert;
    case Update;
    case Delete;
    case Returning;
    case LimitOffset;
    case NullOrdering;
    case Like;
    case InList;
}
