<?php

declare(strict_types=1);

namespace Kongono\DatabaseSql;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Database\DatabaseValue;

/** Associates one target column segment with one explicitly typed value. */
#[Api(ApiStatus::Experimental)]
final readonly class SqlAssignment
{
    public function __construct(
        private SqlIdentifier $column,
        private DatabaseValue $value,
    ) {}

    public function column(): SqlIdentifier
    {
        return $this->column;
    }

    public function value(): DatabaseValue
    {
        return $this->value;
    }
}
