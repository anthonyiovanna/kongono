<?php

declare(strict_types=1);

namespace Kongono\DatabaseSql\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseSql\Exception\InvalidSqlPlanException;
use Kongono\DatabaseSql\SqlAndPredicate;
use Kongono\DatabaseSql\SqlAssignment;
use Kongono\DatabaseSql\SqlComparisonOperator;
use Kongono\DatabaseSql\SqlComparisonPredicate;
use Kongono\DatabaseSql\SqlDialectCapabilities;
use Kongono\DatabaseSql\SqlDialectCapability;
use Kongono\DatabaseSql\SqlInPredicate;
use Kongono\DatabaseSql\SqlNotPredicate;
use Kongono\DatabaseSql\SqlNullPredicate;
use Kongono\DatabaseSql\SqlOrder;
use Kongono\DatabaseSql\SqlOrPredicate;
use Kongono\DatabaseSql\SqlPredicate;
use Kongono\DatabaseSql\SqlProjection;

/** Centralizes package-owned list, uniqueness, and capability validation. */
#[Api(ApiStatus::Internal)]
final class SqlValidation
{
    public static function assertPredicate(SqlPredicate $predicate): void
    {
        if (
            !$predicate instanceof SqlComparisonPredicate
            && !$predicate instanceof SqlNullPredicate
            && !$predicate instanceof SqlInPredicate
            && !$predicate instanceof SqlAndPredicate
            && !$predicate instanceof SqlOrPredicate
            && !$predicate instanceof SqlNotPredicate
        ) {
            throw InvalidSqlPlanException::validation();
        }
    }

    /** @param array<array-key, mixed> $values
     *  @return list<SqlProjection>
     */
    public static function projections(array $values, bool $nonEmpty): array
    {
        if (!array_is_list($values) || count($values) > 4_096 || ($nonEmpty && [] === $values)) {
            throw InvalidSqlPlanException::validation();
        }
        $detached = [];
        $aliases = [];
        foreach ($values as $value) {
            if (!$value instanceof SqlProjection) {
                throw InvalidSqlPlanException::validation();
            }
            $alias = $value->alias()?->value();
            if (null !== $alias && isset($aliases[$alias])) {
                throw InvalidSqlPlanException::validation();
            }
            if (null !== $alias) {
                $aliases[$alias] = true;
            }
            $detached[] = $value;
        }

        return $detached;
    }

    /** @param array<array-key, mixed> $values
     *  @return list<SqlAssignment>
     */
    public static function assignments(array $values): array
    {
        if (!array_is_list($values) || [] === $values || count($values) > 4_096) {
            throw InvalidSqlPlanException::validation();
        }
        $detached = [];
        $columns = [];
        foreach ($values as $value) {
            if (!$value instanceof SqlAssignment || isset($columns[$value->column()->value()])) {
                throw InvalidSqlPlanException::validation();
            }
            $columns[$value->column()->value()] = true;
            $detached[] = $value;
        }

        return $detached;
    }

    /** @param array<array-key, mixed> $values
     *  @return list<SqlOrder>
     */
    public static function orders(array $values): array
    {
        if (!array_is_list($values) || count($values) > 4_096) {
            throw InvalidSqlPlanException::validation();
        }
        $detached = [];
        foreach ($values as $value) {
            if (!$value instanceof SqlOrder) {
                throw InvalidSqlPlanException::validation();
            }
            $detached[] = $value;
        }

        return $detached;
    }

    /** @return list<SqlDialectCapability> */
    public static function predicateCapabilities(?SqlPredicate $predicate): array
    {
        $present = [];
        if (null !== $predicate) {
            self::assertPredicate($predicate);
            self::collectPredicateCapabilities($predicate, $present);
        }
        $ordered = [];
        foreach (SqlDialectCapability::cases() as $capability) {
            if (isset($present[$capability->name])) {
                $ordered[] = $capability;
            }
        }

        return $ordered;
    }

    /** @param list<SqlDialectCapability> $capabilities */
    public static function capabilitySet(array $capabilities): SqlDialectCapabilities
    {
        $present = [];
        foreach ($capabilities as $capability) {
            $present[$capability->name] = $capability;
        }

        return new SqlDialectCapabilities(array_values($present));
    }

    /** @param array<string, true> $present */
    private static function collectPredicateCapabilities(SqlPredicate $predicate, array &$present): void
    {
        if ($predicate instanceof SqlComparisonPredicate && SqlComparisonOperator::Like === $predicate->operator()) {
            $present[SqlDialectCapability::Like->name] = true;
        } elseif ($predicate instanceof SqlInPredicate) {
            $present[SqlDialectCapability::InList->name] = true;
        } elseif ($predicate instanceof SqlAndPredicate || $predicate instanceof SqlOrPredicate) {
            foreach ($predicate->predicates() as $child) {
                self::collectPredicateCapabilities($child, $present);
            }
        } elseif ($predicate instanceof SqlNotPredicate) {
            self::collectPredicateCapabilities($predicate->predicate(), $present);
        }
    }
}
