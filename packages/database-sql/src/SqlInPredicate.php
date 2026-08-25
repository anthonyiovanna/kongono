<?php

declare(strict_types=1);

namespace Kongono\DatabaseSql;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Database\DatabaseValue;
use Kongono\DatabaseSql\Exception\InvalidSqlPlanException;

/** Tests one explicit column against one ordered non-empty value list. */
#[Api(ApiStatus::Experimental)]
final readonly class SqlInPredicate implements SqlPredicate
{
    /** @var list<DatabaseValue> */
    private array $values;

    /** @param array<array-key, mixed> $values */
    public function __construct(private SqlColumn $column, array $values)
    {
        if (!array_is_list($values) || [] === $values || count($values) > 65_535) {
            throw InvalidSqlPlanException::validation();
        }
        $detached = [];
        foreach ($values as $value) {
            if (!$value instanceof DatabaseValue) {
                throw InvalidSqlPlanException::validation();
            }
            $detached[] = $value;
        }
        $this->values = $detached;
    }

    public function column(): SqlColumn
    {
        return $this->column;
    }

    /** @return list<DatabaseValue> */
    public function values(): array
    {
        return [...$this->values];
    }
}
