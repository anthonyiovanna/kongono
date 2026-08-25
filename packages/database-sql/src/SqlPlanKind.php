<?php

declare(strict_types=1);

namespace Kongono\DatabaseSql;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Closed version-1 structured plan kinds. */
#[Api(ApiStatus::Experimental)]
enum SqlPlanKind: string
{
    case Select = 'select';
    case Insert = 'insert';
    case Update = 'update';
    case Delete = 'delete';
}
