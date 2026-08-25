<?php

declare(strict_types=1);

namespace Kongono\DatabaseSql;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Database\DatabaseValue;
use Kongono\Database\DatabaseValueKind;
use Kongono\DatabaseSql\Exception\InvalidSqlPlanException;

/** Compares one explicit column with one non-null typed value. */
#[Api(ApiStatus::Experimental)]
final readonly class SqlComparisonPredicate implements SqlPredicate
{
    public function __construct(
        private SqlColumn $column,
        private SqlComparisonOperator $operator,
        private DatabaseValue $value,
    ) {
        if (DatabaseValueKind::Null === $value->kind()) {
            throw InvalidSqlPlanException::validation();
        }
    }

    public function column(): SqlColumn
    {
        return $this->column;
    }

    public function operator(): SqlComparisonOperator
    {
        return $this->operator;
    }

    public function value(): DatabaseValue
    {
        return $this->value;
    }
}
