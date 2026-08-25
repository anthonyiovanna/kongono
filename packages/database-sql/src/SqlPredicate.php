<?php

declare(strict_types=1);

namespace Kongono\DatabaseSql;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Marks one immutable accepted version-1 predicate shape. */
#[Api(ApiStatus::Experimental)]
interface SqlPredicate {}
