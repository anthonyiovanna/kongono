<?php

declare(strict_types=1);

namespace Kongono\DatabaseSql;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Projects one explicit column with an optional exact alias. */
#[Api(ApiStatus::Experimental)]
final readonly class SqlProjection
{
    public function __construct(
        private SqlColumn $column,
        private ?SqlIdentifier $alias = null,
    ) {}

    public function column(): SqlColumn
    {
        return $this->column;
    }

    public function alias(): ?SqlIdentifier
    {
        return $this->alias;
    }
}
