<?php

declare(strict_types=1);

namespace Kongono\DatabaseSqlPgsql\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Database\DatabaseParameter;
use Kongono\Database\DatabaseStatement;
use Kongono\Database\DatabaseValue;
use Kongono\Database\ResultExpectation;
use Kongono\DatabaseSql\DeletePlan;
use Kongono\DatabaseSql\Exception\InvalidSqlPlanException;
use Kongono\DatabaseSql\InsertPlan;
use Kongono\DatabaseSql\SelectPlan;
use Kongono\DatabaseSql\SqlAndPredicate;
use Kongono\DatabaseSql\SqlAssignment;
use Kongono\DatabaseSql\SqlColumn;
use Kongono\DatabaseSql\SqlComparisonOperator;
use Kongono\DatabaseSql\SqlComparisonPredicate;
use Kongono\DatabaseSql\SqlIdentifier;
use Kongono\DatabaseSql\SqlInPredicate;
use Kongono\DatabaseSql\SqlNotPredicate;
use Kongono\DatabaseSql\SqlNullOrdering;
use Kongono\DatabaseSql\SqlNullPredicate;
use Kongono\DatabaseSql\SqlNullTest;
use Kongono\DatabaseSql\SqlOrder;
use Kongono\DatabaseSql\SqlOrPredicate;
use Kongono\DatabaseSql\SqlPredicate;
use Kongono\DatabaseSql\SqlProjection;
use Kongono\DatabaseSql\SqlQualifiedName;
use Kongono\DatabaseSql\SqlSortDirection;
use Kongono\DatabaseSql\UpdatePlan;

/** Owns only one compile call's local parameter allocation and output assembly. */
#[Api(ApiStatus::Internal)]
final class PostgreSqlCompilation
{
    /** @var list<DatabaseParameter> */
    private array $parameters = [];

    public function compile(SelectPlan|InsertPlan|UpdatePlan|DeletePlan $plan): DatabaseStatement
    {
        return match (true) {
            $plan instanceof SelectPlan => $this->select($plan),
            $plan instanceof InsertPlan => $this->insert($plan),
            $plan instanceof UpdatePlan => $this->update($plan),
            $plan instanceof DeletePlan => $this->delete($plan),
        };
    }

    public static function parameterCount(SelectPlan|InsertPlan|UpdatePlan|DeletePlan $plan): int
    {
        return match (true) {
            $plan instanceof SelectPlan => self::predicateParameterCount($plan->predicate()) + (null === $plan->limit() ? 0 : 1) + (null === $plan->offset() ? 0 : 1),
            $plan instanceof InsertPlan => count($plan->assignments()),
            $plan instanceof UpdatePlan => count($plan->assignments()) + self::predicateParameterCount($plan->scope()->predicate()),
            $plan instanceof DeletePlan => self::predicateParameterCount($plan->scope()->predicate()),
        };
    }

    /** @return list<SqlIdentifier> */
    public static function identifiers(SelectPlan|InsertPlan|UpdatePlan|DeletePlan $plan): array
    {
        $identifiers = [];
        if ($plan instanceof SelectPlan) {
            self::qualifiedIdentifiers($plan->source(), $identifiers);
            self::appendIdentifier($plan->sourceAlias(), $identifiers);
            self::projectionIdentifiers($plan->projections(), $identifiers);
            self::predicateIdentifiers($plan->predicate(), $identifiers);
            foreach ($plan->orders() as $order) {
                self::columnIdentifiers($order->column(), $identifiers);
            }
        } elseif ($plan instanceof InsertPlan) {
            self::qualifiedIdentifiers($plan->target(), $identifiers);
            self::assignmentIdentifiers($plan->assignments(), $identifiers);
            self::projectionIdentifiers($plan->returning(), $identifiers);
        } elseif ($plan instanceof UpdatePlan) {
            self::qualifiedIdentifiers($plan->target(), $identifiers);
            self::assignmentIdentifiers($plan->assignments(), $identifiers);
            self::predicateIdentifiers($plan->scope()->predicate(), $identifiers);
            self::projectionIdentifiers($plan->returning(), $identifiers);
        } else {
            self::qualifiedIdentifiers($plan->target(), $identifiers);
            self::predicateIdentifiers($plan->scope()->predicate(), $identifiers);
            self::projectionIdentifiers($plan->returning(), $identifiers);
        }

        return $identifiers;
    }

    private function select(SelectPlan $plan): DatabaseStatement
    {
        $sql = 'SELECT ' . $this->projections($plan->projections()) . ' FROM ' . $this->qualified($plan->source());
        if (null !== $plan->sourceAlias()) {
            $sql .= ' AS ' . $this->identifier($plan->sourceAlias());
        }
        if (null !== $plan->predicate()) {
            $sql .= ' WHERE ' . $this->predicate($plan->predicate());
        }
        if ([] !== $plan->orders()) {
            $sql .= ' ORDER BY ' . implode(', ', array_map($this->order(...), $plan->orders()));
        }
        if (null !== $plan->limit()) {
            $sql .= ' LIMIT ' . $this->parameter(DatabaseValue::integer($plan->limit()));
        }
        if (null !== $plan->offset()) {
            $sql .= ' OFFSET ' . $this->parameter(DatabaseValue::integer($plan->offset()));
        }

        return new DatabaseStatement($sql, $this->parameters, ResultExpectation::Rows);
    }

    private function insert(InsertPlan $plan): DatabaseStatement
    {
        $assignments = $plan->assignments();
        $columns = implode(', ', array_map(fn(SqlAssignment $assignment): string => $this->identifier($assignment->column()), $assignments));
        $values = implode(', ', array_map(fn(SqlAssignment $assignment): string => $this->parameter($assignment->value()), $assignments));
        $sql = 'INSERT INTO ' . $this->qualified($plan->target()) . ' (' . $columns . ') VALUES (' . $values . ')';
        if ([] !== $plan->returning()) {
            $sql .= ' RETURNING ' . $this->projections($plan->returning());
        }

        return new DatabaseStatement($sql, $this->parameters, [] === $plan->returning() ? ResultExpectation::Completion : ResultExpectation::Rows);
    }

    private function update(UpdatePlan $plan): DatabaseStatement
    {
        $assignments = implode(', ', array_map(
            fn(SqlAssignment $assignment): string => $this->identifier($assignment->column()) . ' = ' . $this->parameter($assignment->value()),
            $plan->assignments(),
        ));
        $sql = 'UPDATE ' . $this->qualified($plan->target()) . ' SET ' . $assignments;
        if (null !== $plan->scope()->predicate()) {
            $sql .= ' WHERE ' . $this->predicate($plan->scope()->predicate());
        }
        if ([] !== $plan->returning()) {
            $sql .= ' RETURNING ' . $this->projections($plan->returning());
        }

        return new DatabaseStatement($sql, $this->parameters, [] === $plan->returning() ? ResultExpectation::Completion : ResultExpectation::Rows);
    }

    private function delete(DeletePlan $plan): DatabaseStatement
    {
        $sql = 'DELETE FROM ' . $this->qualified($plan->target());
        if (null !== $plan->scope()->predicate()) {
            $sql .= ' WHERE ' . $this->predicate($plan->scope()->predicate());
        }
        if ([] !== $plan->returning()) {
            $sql .= ' RETURNING ' . $this->projections($plan->returning());
        }

        return new DatabaseStatement($sql, $this->parameters, [] === $plan->returning() ? ResultExpectation::Completion : ResultExpectation::Rows);
    }

    /** @param list<SqlProjection> $projections */
    private function projections(array $projections): string
    {
        return implode(', ', array_map(function (SqlProjection $projection): string {
            $sql = $this->column($projection->column());
            if (null !== $projection->alias()) {
                $sql .= ' AS ' . $this->identifier($projection->alias());
            }

            return $sql;
        }, $projections));
    }

    private function predicate(SqlPredicate $predicate): string
    {
        return match (true) {
            $predicate instanceof SqlComparisonPredicate => $this->column($predicate->column()) . ' ' . $this->operator($predicate->operator()) . ' ' . $this->parameter($predicate->value()),
            $predicate instanceof SqlNullPredicate => $this->column($predicate->column()) . (SqlNullTest::IsNull === $predicate->test() ? ' IS NULL' : ' IS NOT NULL'),
            $predicate instanceof SqlInPredicate => $this->column($predicate->column()) . ' IN (' . implode(', ', array_map($this->parameter(...), $predicate->values())) . ')',
            $predicate instanceof SqlAndPredicate => '(' . implode(' AND ', array_map($this->predicate(...), $predicate->predicates())) . ')',
            $predicate instanceof SqlOrPredicate => '(' . implode(' OR ', array_map($this->predicate(...), $predicate->predicates())) . ')',
            $predicate instanceof SqlNotPredicate => 'NOT (' . $this->predicate($predicate->predicate()) . ')',
            default => throw InvalidSqlPlanException::validation(),
        };
    }

    private function order(SqlOrder $order): string
    {
        $sql = $this->column($order->column()) . (SqlSortDirection::Ascending === $order->direction() ? ' ASC' : ' DESC');
        $sql .= match ($order->nullOrdering()) {
            SqlNullOrdering::Default => '',
            SqlNullOrdering::First => ' NULLS FIRST',
            SqlNullOrdering::Last => ' NULLS LAST',
        };

        return $sql;
    }

    private function operator(SqlComparisonOperator $operator): string
    {
        return match ($operator) {
            SqlComparisonOperator::Equal => '=',
            SqlComparisonOperator::NotEqual => '<>',
            SqlComparisonOperator::LessThan => '<',
            SqlComparisonOperator::LessThanOrEqual => '<=',
            SqlComparisonOperator::GreaterThan => '>',
            SqlComparisonOperator::GreaterThanOrEqual => '>=',
            SqlComparisonOperator::Like => 'LIKE',
        };
    }

    private function parameter(DatabaseValue $value): string
    {
        $name = sprintf('kgn_p%05d', count($this->parameters) + 1);
        $this->parameters[] = new DatabaseParameter($name, $value);

        return ':' . $name;
    }

    private function qualified(SqlQualifiedName $name): string
    {
        return implode('.', array_map($this->identifier(...), $name->segments()));
    }

    private function column(SqlColumn $column): string
    {
        $sql = '';
        if (null !== $column->qualifier()) {
            $sql .= $this->identifier($column->qualifier()) . '.';
        }

        return $sql . $this->identifier($column->identifier());
    }

    private function identifier(SqlIdentifier $identifier): string
    {
        return '"' . str_replace('"', '""', $identifier->value()) . '"';
    }

    private static function predicateParameterCount(?SqlPredicate $predicate): int
    {
        return match (true) {
            null === $predicate, $predicate instanceof SqlNullPredicate => 0,
            $predicate instanceof SqlComparisonPredicate => 1,
            $predicate instanceof SqlInPredicate => count($predicate->values()),
            $predicate instanceof SqlAndPredicate, $predicate instanceof SqlOrPredicate => array_sum(array_map(self::predicateParameterCount(...), $predicate->predicates())),
            $predicate instanceof SqlNotPredicate => self::predicateParameterCount($predicate->predicate()),
            default => throw InvalidSqlPlanException::validation(),
        };
    }

    /** @param list<SqlIdentifier> $identifiers */
    private static function appendIdentifier(?SqlIdentifier $identifier, array &$identifiers): void
    {
        if (null !== $identifier) {
            $identifiers[] = $identifier;
        }
    }

    /** @param list<SqlIdentifier> $identifiers */
    private static function qualifiedIdentifiers(SqlQualifiedName $name, array &$identifiers): void
    {
        array_push($identifiers, ...$name->segments());
    }

    /** @param list<SqlProjection> $projections
     *  @param list<SqlIdentifier> $identifiers
     */
    private static function projectionIdentifiers(array $projections, array &$identifiers): void
    {
        foreach ($projections as $projection) {
            self::columnIdentifiers($projection->column(), $identifiers);
            self::appendIdentifier($projection->alias(), $identifiers);
        }
    }

    /** @param list<SqlAssignment> $assignments
     *  @param list<SqlIdentifier> $identifiers
     */
    private static function assignmentIdentifiers(array $assignments, array &$identifiers): void
    {
        foreach ($assignments as $assignment) {
            $identifiers[] = $assignment->column();
        }
    }

    /** @param list<SqlIdentifier> $identifiers */
    private static function columnIdentifiers(SqlColumn $column, array &$identifiers): void
    {
        self::appendIdentifier($column->qualifier(), $identifiers);
        $identifiers[] = $column->identifier();
    }

    /** @param list<SqlIdentifier> $identifiers */
    private static function predicateIdentifiers(?SqlPredicate $predicate, array &$identifiers): void
    {
        if ($predicate instanceof SqlComparisonPredicate || $predicate instanceof SqlNullPredicate || $predicate instanceof SqlInPredicate) {
            self::columnIdentifiers($predicate->column(), $identifiers);
        } elseif ($predicate instanceof SqlAndPredicate || $predicate instanceof SqlOrPredicate) {
            foreach ($predicate->predicates() as $child) {
                self::predicateIdentifiers($child, $identifiers);
            }
        } elseif ($predicate instanceof SqlNotPredicate) {
            self::predicateIdentifiers($predicate->predicate(), $identifiers);
        }
    }
}
