<?php

declare(strict_types=1);

namespace Kongono\DatabaseSqlPgsql\Tests;

use Fiber;
use Kongono\Database\DatabaseValue;
use Kongono\Database\ResultExpectation;
use Kongono\DatabaseSql\DeletePlan;
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
use Kongono\DatabaseSql\SqlIdentifier;
use Kongono\DatabaseSql\SqlInPredicate;
use Kongono\DatabaseSql\SqlMutationScope;
use Kongono\DatabaseSql\SqlNotPredicate;
use Kongono\DatabaseSql\SqlNullOrdering;
use Kongono\DatabaseSql\SqlNullPredicate;
use Kongono\DatabaseSql\SqlNullTest;
use Kongono\DatabaseSql\SqlOrder;
use Kongono\DatabaseSql\SqlOrPredicate;
use Kongono\DatabaseSql\SqlPlan;
use Kongono\DatabaseSql\SqlPlanKind;
use Kongono\DatabaseSql\SqlProjection;
use Kongono\DatabaseSql\SqlQualifiedName;
use Kongono\DatabaseSql\SqlSortDirection;
use Kongono\DatabaseSql\UpdatePlan;
use Kongono\DatabaseSqlPgsql\Internal\PostgreSqlCompilation;
use Kongono\DatabaseSqlPgsql\PostgreSql17Compiler;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PostgreSql17CompilerTest extends TestCase
{
    public function testSelectCompilesCanonicalNestedSqlAndOccurrenceOrderedParameters(): void
    {
        $compiler = new PostgreSql17Compiler();
        $first = DatabaseValue::integer(7);
        $repeated = DatabaseValue::text('same');
        $predicate = new SqlAndPredicate([
            new SqlComparisonPredicate($this->column('id', 't'), SqlComparisonOperator::GreaterThan, $first),
            new SqlOrPredicate([
                new SqlInPredicate($this->column('name', 't'), [$repeated, $repeated]),
                new SqlNotPredicate(new SqlNullPredicate($this->column('note', 't'), SqlNullTest::IsNull)),
            ]),
        ]);
        $plan = new SelectPlan(
            new SqlQualifiedName([$this->id('app'), $this->id('items')]),
            [
                new SqlProjection($this->column('id', 't'), $this->id('item_id')),
                new SqlProjection($this->column('a"b', 't')),
            ],
            $this->id('t'),
            $predicate,
            [new SqlOrder($this->column('name', 't'), SqlSortDirection::Descending, SqlNullOrdering::Last)],
            10,
            2,
        );
        $statement = $compiler->compile($plan);

        self::assertSame('SELECT "t"."id" AS "item_id", "t"."a""b" FROM "app"."items" AS "t" WHERE ("t"."id" > :kgn_p00001 AND ("t"."name" IN (:kgn_p00002, :kgn_p00003) OR NOT ("t"."note" IS NULL))) ORDER BY "t"."name" DESC NULLS LAST LIMIT :kgn_p00004 OFFSET :kgn_p00005', $statement->sql());
        self::assertSame(['kgn_p00001', 'kgn_p00002', 'kgn_p00003', 'kgn_p00004', 'kgn_p00005'], array_map(static fn($parameter): string => $parameter->name(), $statement->parameters()));
        self::assertSame([$first, $repeated, $repeated], array_map(static fn($parameter) => $parameter->value(), array_slice($statement->parameters(), 0, 3)));
        self::assertSame(ResultExpectation::Rows, $statement->expectation());
    }

    /** @param non-empty-string $sqlOperator */
    #[DataProvider('operatorProvider')]
    public function testEveryComparisonOperatorCompilesExactly(SqlComparisonOperator $operator, string $sqlOperator): void
    {
        $plan = new SelectPlan(
            $this->qualifiedName('items'),
            [new SqlProjection($this->column('id'))],
            predicate: new SqlComparisonPredicate($this->column('id'), $operator, DatabaseValue::integer(1)),
        );
        self::assertStringContainsString('"id" ' . $sqlOperator . ' :kgn_p00001', (new PostgreSql17Compiler())->compile($plan)->sql());
    }

    /** @return iterable<string, array{SqlComparisonOperator, non-empty-string}> */
    public static function operatorProvider(): iterable
    {
        yield 'equal' => [SqlComparisonOperator::Equal, '='];
        yield 'not equal' => [SqlComparisonOperator::NotEqual, '<>'];
        yield 'less' => [SqlComparisonOperator::LessThan, '<'];
        yield 'less or equal' => [SqlComparisonOperator::LessThanOrEqual, '<='];
        yield 'greater' => [SqlComparisonOperator::GreaterThan, '>'];
        yield 'greater or equal' => [SqlComparisonOperator::GreaterThanOrEqual, '>='];
        yield 'like' => [SqlComparisonOperator::Like, 'LIKE'];
    }

    public function testInsertUpdateDeleteReturningAndAllRowsCompileExactly(): void
    {
        $compiler = new PostgreSql17Compiler();
        $assignments = [
            new SqlAssignment($this->id('a'), DatabaseValue::integer(1)),
            new SqlAssignment($this->id('b'), DatabaseValue::text('two')),
        ];
        $returning = [new SqlProjection($this->column('id'), $this->id('returned'))];

        $insert = $compiler->compile(new InsertPlan($this->qualifiedName('t'), $assignments, $returning));
        self::assertSame('INSERT INTO "t" ("a", "b") VALUES (:kgn_p00001, :kgn_p00002) RETURNING "id" AS "returned"', $insert->sql());
        self::assertSame(ResultExpectation::Rows, $insert->expectation());

        $updateAll = $compiler->compile(new UpdatePlan($this->qualifiedName('t'), $assignments, SqlMutationScope::allRows()));
        self::assertSame('UPDATE "t" SET "a" = :kgn_p00001, "b" = :kgn_p00002', $updateAll->sql());
        self::assertSame(ResultExpectation::Completion, $updateAll->expectation());

        $scope = SqlMutationScope::forPredicate(new SqlNullPredicate($this->column('gone'), SqlNullTest::IsNotNull));
        $delete = $compiler->compile(new DeletePlan($this->qualifiedName('t'), $scope, $returning));
        self::assertSame('DELETE FROM "t" WHERE "gone" IS NOT NULL RETURNING "id" AS "returned"', $delete->sql());
        self::assertSame(ResultExpectation::Rows, $delete->expectation());

        $deleteAll = $compiler->compile(new DeletePlan($this->qualifiedName('t'), SqlMutationScope::allRows()));
        self::assertSame('DELETE FROM "t"', $deleteAll->sql());
        self::assertSame(ResultExpectation::Completion, $deleteAll->expectation());
    }

    public function testCompilerReuseAndFibersShareNoCounterOrMutableState(): void
    {
        $compiler = new PostgreSql17Compiler();
        $plan = new InsertPlan($this->qualifiedName('t'), [new SqlAssignment($this->id('id'), DatabaseValue::integer(1))]);
        self::assertSame('kgn_p00001', $compiler->compile($plan)->parameters()[0]->name());
        self::assertSame('kgn_p00001', $compiler->compile($plan)->parameters()[0]->name());

        $one = new Fiber(static fn() => $compiler->compile($plan)->parameters()[0]->name());
        $two = new Fiber(static fn() => $compiler->compile($plan)->parameters()[0]->name());
        $one->start();
        $two->start();
        self::assertSame('kgn_p00001', $one->getReturn());
        self::assertSame('kgn_p00001', $two->getReturn());
    }

    public function testSourceArraysAndPlansRemainUnchanged(): void
    {
        $assignments = [new SqlAssignment($this->id('id'), DatabaseValue::integer(1))];
        $plan = new InsertPlan($this->qualifiedName('t'), $assignments);
        $before = $plan->assignments();
        $assignments[] = new SqlAssignment($this->id('later'), DatabaseValue::integer(2));
        $returned = $plan->assignments();
        $returned[] = $assignments[1];
        (new PostgreSql17Compiler())->compile($plan);

        self::assertSame($before, $plan->assignments());
    }

    public function testPostgreSqlIdentifierBoundIsExactAndNonDisclosing(): void
    {
        $compiler = new PostgreSql17Compiler();
        self::assertSame('SELECT "' . str_repeat('a', 63) . '" FROM "t"', $compiler->compile(new SelectPlan($this->qualifiedName('t'), [new SqlProjection($this->column(str_repeat('a', 63))) ]))->sql());
        $rejected = str_repeat('private-sentinel-', 4);
        try {
            $compiler->compile(new SelectPlan($this->qualifiedName('t'), [new SqlProjection($this->column($rejected))]));
            self::fail('Expected PostgreSQL identifier rejection.');
        } catch (SqlCompilationException $exception) {
            self::assertStringNotContainsString($rejected, $exception->getMessage());
            self::assertStringNotContainsString($rejected, (string) $exception);
        }
    }

    public function testForeignPlanFailsBeforeItsAccessorsRun(): void
    {
        $foreign = new class implements SqlPlan {
            public function kind(): SqlPlanKind
            {
                throw new \RuntimeException('private-sentinel');
            }

            public function requiredCapabilities(): SqlDialectCapabilities
            {
                throw new \RuntimeException('private-sentinel');
            }
        };
        $this->expectException(UnsupportedDialectCapabilityException::class);
        (new PostgreSql17Compiler())->compile($foreign);
    }

    public function testParameterCountBelowAtAndAboveMaximumFailsBeforeStatementConstruction(): void
    {
        $value = DatabaseValue::integer(1);
        $below = new DeletePlan($this->qualifiedName('t'), SqlMutationScope::forPredicate(new SqlInPredicate($this->column('id'), array_fill(0, 65_534, $value))));
        $at = new DeletePlan($this->qualifiedName('t'), SqlMutationScope::forPredicate(new SqlInPredicate($this->column('id'), array_fill(0, 65_535, $value))));
        $above = new UpdatePlan(
            $this->qualifiedName('t'),
            [new SqlAssignment($this->id('flag'), $value)],
            SqlMutationScope::forPredicate(new SqlInPredicate($this->column('id'), array_fill(0, 65_535, $value))),
        );

        self::assertSame(65_534, PostgreSqlCompilation::parameterCount($below));
        self::assertSame(65_535, PostgreSqlCompilation::parameterCount($at));
        self::assertCount(65_535, (new PostgreSql17Compiler())->compile($at)->parameters());
        try {
            (new PostgreSql17Compiler())->compile($above);
            self::fail('Expected parameter overflow rejection.');
        } catch (SqlCompilationException $exception) {
            self::assertSame('KGN-SQL-1003', $exception->diagnosticId());
            self::assertSame(['database-sql', 'compilation', 'update', 'postgresql-17', 65_536], array_map(static fn($field) => $field->value(), $exception->diagnosticContext()->fields()));
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
