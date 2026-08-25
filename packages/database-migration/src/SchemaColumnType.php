<?php

declare(strict_types=1);

namespace Kongono\DatabaseMigration;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Closed provider-neutral version-1 schema column vocabulary. */
#[Api(ApiStatus::Experimental)]
enum SchemaColumnType: string
{
    case Boolean = 'boolean';
    case Int64 = 'int64';
    case Float64 = 'float64';
    case Text = 'text';
    case Binary = 'binary';
    case Uuid = 'uuid';
    case Json = 'json';
    case TimestampTz = 'timestamp_tz';
}
