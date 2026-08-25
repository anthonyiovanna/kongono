<?php

declare(strict_types=1);

namespace Kongono\Database;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Database\Exception\DatabaseResultException;
use Kongono\Database\Exception\InvalidDatabaseValueException;

/** Preserves ordered columns and values without associative-label overwrite. */
#[Api(ApiStatus::Experimental)]
final readonly class DatabaseRow
{
    /** @var list<DatabaseColumn> */
    private array $columns;

    /** @var list<DatabaseValue> */
    private array $values;

    /**
     * @param array<array-key, mixed> $columns
     * @param array<array-key, mixed> $values
     */
    public function __construct(array $columns, array $values)
    {
        if (!array_is_list($columns) || !array_is_list($values) || count($columns) !== count($values) || count($columns) > 4_096) {
            throw InvalidDatabaseValueException::validation();
        }
        $detachedColumns = [];
        $detachedValues = [];
        foreach ($columns as $index => $column) {
            if (!$column instanceof DatabaseColumn || $column->ordinal() !== $index || !$values[$index] instanceof DatabaseValue) {
                throw InvalidDatabaseValueException::validation();
            }
            $detachedColumns[] = $column;
            $detachedValues[] = $values[$index];
        }
        $this->columns = $detachedColumns;
        $this->values = $detachedValues;
    }

    /** @return list<DatabaseColumn> */
    public function columns(): array
    {
        return [...$this->columns];
    }

    /** @return list<DatabaseValue> */
    public function values(): array
    {
        return [...$this->values];
    }

    public function at(int $ordinal): DatabaseValue
    {
        if ($ordinal < 0 || !isset($this->values[$ordinal])) {
            throw DatabaseResultException::access();
        }

        return $this->values[$ordinal];
    }

    public function named(string $label): DatabaseValue
    {
        $match = null;
        foreach ($this->columns as $index => $column) {
            if ($column->label() !== $label) {
                continue;
            }
            if (null !== $match) {
                throw DatabaseResultException::access();
            }
            $match = $index;
        }
        if (null === $match) {
            throw DatabaseResultException::access();
        }

        return $this->values[$match];
    }
}
