<?php

declare(strict_types=1);

namespace Kongono\DatabaseSql;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Declares whether null ordering is dialect-default or explicit. */
#[Api(ApiStatus::Experimental)]
enum SqlNullOrdering
{
    case Default;
    case First;
    case Last;
}
