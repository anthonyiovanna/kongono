<?php

declare(strict_types=1);

namespace Kongono\DatabaseSql;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Closed explicit null-test vocabulary. */
#[Api(ApiStatus::Experimental)]
enum SqlNullTest
{
    case IsNull;
    case IsNotNull;
}
