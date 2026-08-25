<?php

declare(strict_types=1);

namespace Kongono\DatabaseSql\Tests;

use Kongono\Database\DatabaseValue;
use Kongono\DatabaseSql\DeletePlan;
use Kongono\DatabaseSql\Diagnostic\SqlDiagnostic;
use Kongono\DatabaseSql\Exception\ExceptionInterface;
use Kongono\DatabaseSql\Exception\InvalidSqlPlanException;
use Kongono\DatabaseSql\Exception\SqlCompilationException;
use Kongono\DatabaseSql\Exception\UnsupportedDialectCapabilityException;
use Kongono\DatabaseSql\InsertPlan;
use Kongono\DatabaseSql\SelectPlan;
use Kongono\DatabaseSql\SqlAndPredicate;
use Kongono\DatabaseSql\SqlAssignment;
use Kongono\DatabaseSql\SqlColumn;
use Kongono\DatabaseSql\SqlComparisonOperator;
use Kongono\DatabaseSql\SqlComparisonPredicate;
use Kongono\DatabaseSql\SqlDialectCapabilities;
use Kongono\DatabaseSql\SqlDialectCapability;
use Kongono\DatabaseSql\SqlIdentifier;
use Kongono\DatabaseSql\SqlInPredicate;
use Kongono\DatabaseSql\SqlMutationScope;
use Kongono\DatabaseSql\SqlNotPredicate;
use Kongono\DatabaseSql\SqlNullOrdering;
use Kongono\DatabaseSql\SqlNullPredicate;
use Kongono\DatabaseSql\SqlNullTest;
use Kongono\DatabaseSql\SqlOrder;
use Kongono\DatabaseSql\SqlOrPredicate;
use Kongono\DatabaseSql\SqlPlanKind;
use Kongono\DatabaseSql\SqlPredicate;
use Kongono\DatabaseSql\SqlProjection;
use Kongono\DatabaseSql\SqlQualifiedName;
use Kongono\DatabaseSql\SqlSortDirection;
use Kongono\DatabaseSql\UpdatePlan;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SqlPlanContractTest extends TestCase
{
    public function testIdentifiersRetainExactBoundedSegmentsAndQualificationDetaches(): void
    {
        $punctuation = new SqlIdentifier(' order."part.name" ');
        $maximum = new SqlIdentifier(str_repeat('é', 64));
        $segments = [new SqlIdentifier('schema'), $punctuation];
        $qualified = new SqlQualifiedName($segments);
        $segments[] = new SqlIdentifier('later');
        $returned = $qualified->segments();
        $returned[0] = new SqlIdentifier('changed');

        self::assertSame(' order."part.name" ', $punctuation->value());
        self::assertSame(128, strlen($maximum->value()));
        self::assertSame(['schema', ' order."part.name" '], array_map(static fn(SqlIdentifier $item): string => $item->value(), $qualified->segments()));
    }

    #[DataProvider('invalidIdentifierProvider')]
    public function testInvalidIdentifiersFailWithoutDisclosure(string $value): void
    {
        try {
            new SqlIdentifier($value);
            self::fail('Expected invalid identifier rejection.');
        } catch (InvalidSqlPlanException $exception) {
            self::assertSame('KGN-SQL-1001', $exception->diagnosticId());
            if ('' !== $value) {
                self::assertStringNotContainsString($value, $exception->getMessage());
                self::assertStringNotContainsString($value, (string) $exception);
            }
        }
    }

    /** @return iterable<string, array{string}> */
    public static function invalidIdentifierProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'above bound' => [str_repeat('a', 129)];
        yield 'invalid UTF-8' => ["\xC3\x28"];
        yield 'NUL' => ["a\0b"];
        yield 'control' => ["a\nb"];
        yield 'DEL' => ["a\x7Fb"];
    }

    public function testEveryPlanShapeIsDetachedInspectableAndExplicitAboutMutationScope(): void
    {
        $projection = new SqlProjection($this->column('id'), $this->id('item_id'));
        $projections = [$projection];
        $orders = [new SqlOrder($this->column('id'), SqlSortDirection::Descending, SqlNullOrdering::Last)];
        $predicate = new SqlComparisonPredicate($this->column('id'), SqlComparisonOperator::Equal, DatabaseValue::integer(4));
        $select = new SelectPlan($this->qualifiedName('items'), $projections, $this->id('i'), $predicate, $orders, 10, 2);
        $projections[] = new SqlProjection($this->column('extra'));
        $orders = [];

        $assignments = [new SqlAssignment($this->id('name'), DatabaseValue::text('value'))];
        $insert = new InsertPlan($this->qualifiedName('items'), $assignments, [$projection]);
        $scope = SqlMutationScope::forPredicate($predicate);
        $update = new UpdatePlan($this->qualifiedName('items'), $assignments, $scope);
        $deleteAll = new DeletePlan($this->qualifiedName('items'), SqlMutationScope::allRows(), [$projection]);
        $assignments[] = new SqlAssignment($this->id('later'), DatabaseValue::integer(1));

        self::assertSame(SqlPlanKind::Select, $select->kind());
        self::assertSame([$projection], $select->projections());
        self::assertSame(1, count($select->orders()));
        self::assertSame(10, $select->limit());
        self::assertSame(2, $select->offset());
        self::assertSame(SqlPlanKind::Insert, $insert->kind());
        self::assertSame(1, count($insert->assignments()));
        self::assertSame(SqlPlanKind::Update, $update->kind());
        self::assertSame($predicate, $update->scope()->predicate());
        self::assertFalse($update->scope()->isAllRows());
        self::assertSame(SqlPlanKind::Delete, $deleteAll->kind());
        self::assertTrue($deleteAll->scope()->isAllRows());
        self::assertNull($deleteAll->scope()->predicate());
    }

    public function testEveryPredicateAndEnumCaseIsInspectable(): void
    {
        $column = $this->column('value', 'q');
        foreach (SqlComparisonOperator::cases() as $operator) {
            $comparison = new SqlComparisonPredicate($column, $operator, DatabaseValue::text('x'));
            self::assertSame($operator, $comparison->operator());
            self::assertSame($column, $comparison->column());
        }
        foreach (SqlNullTest::cases() as $test) {
            self::assertSame($test, (new SqlNullPredicate($column, $test))->test());
        }
        $in = new SqlInPredicate($column, [DatabaseValue::integer(1), DatabaseValue::integer(2)]);
        $and = new SqlAndPredicate([$in, new SqlNullPredicate($column, SqlNullTest::IsNotNull)]);
        $or = new SqlOrPredicate([$and]);
        $not = new SqlNotPredicate($or);

        self::assertSame(2, count($in->values()));
        self::assertSame([$in, $and->predicates()[1]], $and->predicates());
        self::assertSame([$and], $or->predicates());
        self::assertSame($or, $not->predicate());
        self::assertSame([SqlSortDirection::Ascending, SqlSortDirection::Descending], SqlSortDirection::cases());
        self::assertSame([SqlNullOrdering::Default, SqlNullOrdering::First, SqlNullOrdering::Last], SqlNullOrdering::cases());
    }

    public function testInvalidPlanShapesForeignObjectsAndDuplicatesFailClosed(): void
    {
        $assignment = new SqlAssignment($this->id('same'), DatabaseValue::integer(1));
        $projection = new SqlProjection($this->column('id'), $this->id('alias'));
        $foreign = new class implements SqlPredicate {};
        $cases = [
            static fn() => new SqlQualifiedName([]),
            static fn() => new SqlQualifiedName(['foreign']),
            fn() => new SelectPlan($this->qualifiedName('x'), []),
            fn() => new SelectPlan($this->qualifiedName('x'), [$projection], orders: ['foreign']),
            fn() => new SelectPlan($this->qualifiedName('x'), [$projection], limit: -1),
            fn() => new SelectPlan($this->qualifiedName('x'), [$projection], predicate: $foreign),
            fn() => new InsertPlan($this->qualifiedName('x'), []),
            fn() => new InsertPlan($this->qualifiedName('x'), [$assignment, $assignment]),
            fn() => new InsertPlan($this->qualifiedName('x'), [$assignment], [$projection, $projection]),
            fn() => new SqlInPredicate($this->column('x'), []),
            fn() => new SqlInPredicate($this->column('x'), ['foreign']),
            static fn() => new SqlAndPredicate([]),
            static fn() => new SqlOrPredicate([]),
            fn() => SqlMutationScope::forPredicate($foreign),
            fn() => new SqlComparisonPredicate($this->column('x'), SqlComparisonOperator::Equal, DatabaseValue::null()),
        ];

        foreach ($cases as $case) {
            try {
                $case();
                self::fail('Expected invalid plan rejection.');
            } catch (InvalidSqlPlanException $exception) {
                self::assertSame('KGN-SQL-1001', $exception->diagnosticId());
            }
        }
    }

    public function testStructuralAndParameterCollectionBoundsAreExact(): void
    {
        $projection = new SqlProjection($this->column('id'));
        self::assertCount(4_096, (new SelectPlan($this->qualifiedName('x'), array_fill(0, 4_096, $projection)))->projections());
        $this->expectException(InvalidSqlPlanException::class);
        new SelectPlan($this->qualifiedName('x'), array_fill(0, 4_097, $projection));
    }

    public function testInListAcceptsMaximumAndRejectsAboveMaximum(): void
    {
        $value = DatabaseValue::integer(1);
        self::assertCount(65_535, (new SqlInPredicate($this->column('id'), array_fill(0, 65_535, $value)))->values());
        $this->expectException(InvalidSqlPlanException::class);
        new SqlInPredicate($this->column('id'), array_fill(0, 65_536, $value));
    }

    public function testCapabilitySetsAreCanonicalDuplicateFreeAndShapeDerived(): void
    {
        $set = new SqlDialectCapabilities([SqlDialectCapability::InList, SqlDialectCapability::Select]);
        self::assertSame([SqlDialectCapability::Select, SqlDialectCapability::InList], $set->all());
        self::assertTrue($set->has(SqlDialectCapability::Select));
        self::assertFalse($set->has(SqlDialectCapability::Delete));

        $predicate = new SqlAndPredicate([
            new SqlComparisonPredicate($this->column('name'), SqlComparisonOperator::Like, DatabaseValue::text('x')),
            new SqlInPredicate($this->column('id'), [DatabaseValue::integer(1)]),
        ]);
        $plan = new SelectPlan(
            $this->qualifiedName('items'),
            [new SqlProjection($this->column('id'))],
            predicate: $predicate,
            orders: [new SqlOrder($this->column('id'), SqlSortDirection::Ascending, SqlNullOrdering::First)],
            limit: 1,
        );
        self::assertSame([
            SqlDialectCapability::Select,
            SqlDialectCapability::LimitOffset,
            SqlDialectCapability::NullOrdering,
            SqlDialectCapability::Like,
            SqlDialectCapability::InList,
        ], $plan->requiredCapabilities()->all());

        $this->expectException(InvalidSqlPlanException::class);
        new SqlDialectCapabilities([SqlDialectCapability::Select, SqlDialectCapability::Select]);
    }

    public function testDiagnosticsAreClosedUniqueActionableAndNonDisclosing(): void
    {
        self::assertSame(['KGN-SQL-1001', 'KGN-SQL-1002', 'KGN-SQL-1003'], array_column(SqlDiagnostic::cases(), 'value'));
        $failures = [
            InvalidSqlPlanException::validation(),
            UnsupportedDialectCapabilityException::capability(SqlPlanKind::Select, SqlDialectCapability::Returning, 'postgresql-17'),
            SqlCompilationException::parameterOverflow(SqlPlanKind::Update, 'postgresql-17', 65_536),
        ];
        foreach ($failures as $failure) {
            self::assertInstanceOf(ExceptionInterface::class, $failure);
            self::assertNotSame('', $failure->diagnosticTitle());
            self::assertNotSame('', $failure->diagnosticRemediation());
            self::assertSame($failure->diagnosticTitle(), $failure->getMessage());
            self::assertStringNotContainsString('private-sentinel', (string) $failure);
            foreach ($failure->diagnosticContext()->fields() as $field) {
                self::assertStringNotContainsString('private-sentinel', (string) $field->value());
            }
        }
    }

    private function id(string $value): SqlIdentifier
    {
        return new SqlIdentifier($value);
    }

    private function qualifiedName(string $value): SqlQualifiedName
    {
        return new SqlQualifiedName([$this->id($value)]);
    }

    private function column(string $value, ?string $qualifier = null): SqlColumn
    {
        return new SqlColumn($this->id($value), null === $qualifier ? null : $this->id($qualifier));
    }
}
