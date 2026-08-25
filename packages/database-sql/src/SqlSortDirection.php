<?php

declare(strict_types=1);

namespace Kongono\DatabaseSql;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Closed explicit sort-direction vocabulary. */
#[Api(ApiStatus::Experimental)]
enum SqlSortDirection
{
    case Ascending;
    case Descending;
}
