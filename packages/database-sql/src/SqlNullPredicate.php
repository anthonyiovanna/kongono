<?php

declare(strict_types=1);

namespace Kongono\DatabaseSql;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Tests one explicit column for SQL null without allocating a parameter. */
#[Api(ApiStatus::Experimental)]
final readonly class SqlNullPredicate implements SqlPredicate
{
    public function __construct(
        private SqlColumn $column,
        private SqlNullTest $test,
    ) {}

    public function column(): SqlColumn
    {
        return $this->column;
    }

    public function test(): SqlNullTest
    {
        return $this->test;
    }
}
