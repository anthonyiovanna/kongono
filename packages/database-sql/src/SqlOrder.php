<?php

declare(strict_types=1);

namespace Kongono\DatabaseSql;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Stores one explicit ordered sort term. */
#[Api(ApiStatus::Experimental)]
final readonly class SqlOrder
{
    public function __construct(
        private SqlColumn $column,
        private SqlSortDirection $direction,
        private SqlNullOrdering $nullOrdering = SqlNullOrdering::Default,
    ) {}

    public function column(): SqlColumn
    {
        return $this->column;
    }

    public function direction(): SqlSortDirection
    {
        return $this->direction;
    }

    public function nullOrdering(): SqlNullOrdering
    {
        return $this->nullOrdering;
    }
}
