<?php

declare(strict_types=1);

namespace Kongono\DatabaseSql;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Closed version-1 column-to-value comparison vocabulary. */
#[Api(ApiStatus::Experimental)]
enum SqlComparisonOperator
{
    case Equal;
    case NotEqual;
    case LessThan;
    case LessThanOrEqual;
    case GreaterThan;
    case GreaterThanOrEqual;
    case Like;
}
