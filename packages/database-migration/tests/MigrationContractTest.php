<?php

declare(strict_types=1);

namespace Kongono\DatabaseMigration\Tests;

use Kongono\DatabaseMigration\AddColumn;
use Kongono\DatabaseMigration\AppliedMigration;
use Kongono\DatabaseMigration\CreateIndex;
use Kongono\DatabaseMigration\CreateTable;
use Kongono\DatabaseMigration\DestructiveMigrationPolicy;
use Kongono\DatabaseMigration\DropColumn;
use Kongono\DatabaseMigration\DropIndex;
use Kongono\DatabaseMigration\DropTable;
use Kongono\DatabaseMigration\Exception\DestructiveMigrationReviewRequiredException;
use Kongono\DatabaseMigration\Exception\InvalidMigrationDefinitionException;
use Kongono\DatabaseMigration\Exception\MigrationHistoryMismatchException;
use Kongono\DatabaseMigration\Exception\MigrationNotReversibleException;
use Kongono\DatabaseMigration\MigrationCatalog;
use Kongono\DatabaseMigration\MigrationDefinition;
use Kongono\DatabaseMigration\MigrationDirection;
use Kongono\DatabaseMigration\MigrationDirectionPlan;
use Kongono\DatabaseMigration\MigrationHistory;
use Kongono\DatabaseMigration\MigrationPlanner;
use Kongono\DatabaseMigration\MigrationRisk;
use Kongono\DatabaseMigration\RenameColumn;
use Kongono\DatabaseMigration\RenameTable;
use Kongono\DatabaseMigration\SchemaColumn;
use Kongono\DatabaseMigration\SchemaColumnType;
use Kongono\DatabaseSql\SqlIdentifier;
use Kongono\DatabaseSql\SqlQualifiedName;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MigrationContractTest extends TestCase
{
    public function testVersionOneOperationsAreInspectableAndDeriveExactRisk(): void
    {
        $key = new SchemaColumn($this->id('record_id'), SchemaColumnType::Int64, false);
        $text = new SchemaColumn($this->id('payload'), SchemaColumnType::Text, true);
        $table = $this->qualifiedName('app', 'records');
        $index = $this->qualifiedName('app', 'records_payload_idx');
        $operations = [
            new CreateTable($table, [$key, $text], [$key->name()]),
            new DropTable($table),
            new AddColumn($table, $text),
            new DropColumn($table, $text->name()),
            new CreateIndex($index, $table, [$text->name()], true),
            new DropIndex($index),
            new RenameTable($table, $this->id('renamed_records')),
            new RenameColumn($table, $text->name(), $this->id('renamed_payload')),
        ];

        self::assertSame(
            ['create_table', 'drop_table', 'add_column', 'drop_column', 'create_index', 'drop_index', 'rename_table', 'rename_column'],
            array_map(static fn($operation): string => $operation->kind(), $operations),
        );
        self::assertSame(
            [MigrationRisk::Safe, MigrationRisk::Destructive, MigrationRisk::Safe, MigrationRisk::Destructive, MigrationRisk::Safe, MigrationRisk::Destructive, MigrationRisk::Destructive, MigrationRisk::Destructive],
            array_map(static fn($operation): MigrationRisk => $operation->risk(), $operations),
        );
        self::assertSame($table, $operations[0]->table());
        self::assertSame([$key, $text], $operations[0]->columns());
        self::assertSame([$key->name()], $operations[0]->primaryKey());
    }

    #[DataProvider('invalidDefinitionProvider')]
    public function testInvalidDefinitionsFailWithoutDisclosingInput(\Closure $factory): void
    {
        try {
            $factory();
            self::fail('Expected invalid definition failure.');
        } catch (InvalidMigrationDefinitionException $failure) {
            self::assertSame('KGN-MIGRATION-1001', $failure->diagnosticId());
            self::assertStringNotContainsString('sentinel', (string) $failure);
            self::assertStringNotContainsString('sentinel', $failure->getMessage());
        }
    }

    /** @return iterable<string, array{\Closure(): void}> */
    public static function invalidDefinitionProvider(): iterable
    {
        yield 'empty create table' => [static function (): void {
            new CreateTable(new SqlQualifiedName([new SqlIdentifier('sentinel')]), []);
        }];
        yield 'nullable primary key' => [static function (): void {
            $column = new SchemaColumn(new SqlIdentifier('sentinel'), SchemaColumnType::Text, true);
            new CreateTable(new SqlQualifiedName([new SqlIdentifier('records')]), [$column], [$column->name()]);
        }];
        yield 'non-null add' => [static function (): void {
            new AddColumn(
                new SqlQualifiedName([new SqlIdentifier('records')]),
                new SchemaColumn(new SqlIdentifier('sentinel'), SchemaColumnType::Text, false),
            );
        }];
        yield 'false safe risk' => [static function (): void {
            new MigrationDirectionPlan(
                [new DropTable(new SqlQualifiedName([new SqlIdentifier('sentinel')]))],
                MigrationRisk::Safe,
            );
        }];
        yield 'false destructive risk' => [static function (): void {
            $column = new SchemaColumn(new SqlIdentifier('sentinel'), SchemaColumnType::Text, true);
            new MigrationDirectionPlan(
                [new AddColumn(new SqlQualifiedName([new SqlIdentifier('records')]), $column)],
                MigrationRisk::Destructive,
            );
        }];
        yield 'bad migration id' => [static function (): void {
            new MigrationDefinition(1, 'sentinel-invalid', self::safeDirection());
        }];
        yield 'ordinal gap' => [static function (): void {
            new MigrationCatalog([new MigrationDefinition(2, 'sentinel', self::safeDirection())]);
        }];
    }

    public function testCanonicalIdentityIsDeterministicAndBindsRevertSemantics(): void
    {
        $first = $this->definition(true);
        $second = $this->definition(true);
        $irreversible = $this->definition(false);

        self::assertSame($first->canonicalBytes(), $second->canonicalBytes());
        self::assertSame($first->sha256(), $second->sha256());
        self::assertNotSame($first->sha256(), $irreversible->sha256());
        self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/D', $first->sha256());
        self::assertStringNotContainsString("\n", $first->canonicalBytes());
        self::assertSame(
            '{"schema":"kongono.migration-catalog","version":1,"migrations":[{"ordinal":1,"id":"create_records","checksum":"' . $first->sha256() . '"}]}',
            (new MigrationCatalog([$first]))->canonicalBytes(),
        );
    }

    public function testPlannerRequiresExactPrefixAndExplicitDestructiveReview(): void
    {
        $definition = $this->definition(true);
        $catalog = new MigrationCatalog([$definition]);
        $planner = new MigrationPlanner();

        $apply = $planner->applyPending($catalog, new MigrationHistory([]), DestructiveMigrationPolicy::Deny);
        self::assertSame(MigrationDirection::Apply, $apply->direction());
        self::assertSame([1, 1, 1], [$apply->resultingAppliedCount(), count($apply->migrations()), $apply->operationCount()]);
        self::assertFalse($apply->isNoOp());

        $history = new MigrationHistory([new AppliedMigration(1, $definition->id(), $definition->sha256())]);
        self::assertTrue($planner->applyPending($catalog, $history, DestructiveMigrationPolicy::Deny)->isNoOp());
        $this->expectException(DestructiveMigrationReviewRequiredException::class);
        $planner->revertLast($catalog, $history, DestructiveMigrationPolicy::Deny);
    }

    public function testPlannerRevertsOnlyLastAndRejectsIrreversibleOrMismatchedHistory(): void
    {
        $definition = $this->definition(true);
        $catalog = new MigrationCatalog([$definition]);
        $history = new MigrationHistory([new AppliedMigration(1, $definition->id(), $definition->sha256())]);
        $plan = (new MigrationPlanner())->revertLast($catalog, $history, DestructiveMigrationPolicy::Allow);
        self::assertSame([MigrationDirection::Revert, 1, 0, 1], [$plan->direction(), $plan->previousAppliedCount(), $plan->resultingAppliedCount(), count($plan->migrations())]);

        try {
            (new MigrationPlanner())->revertLast(
                new MigrationCatalog([$this->definition(false)]),
                new MigrationHistory([new AppliedMigration(1, 'create_records', $this->definition(false)->sha256())]),
                DestructiveMigrationPolicy::Allow,
            );
            self::fail('Expected irreversibility failure.');
        } catch (MigrationNotReversibleException $failure) {
            self::assertSame('KGN-MIGRATION-1004', $failure->diagnosticId());
        }

        $this->expectException(MigrationHistoryMismatchException::class);
        (new MigrationPlanner())->applyPending(
            $catalog,
            new MigrationHistory([new AppliedMigration(1, $definition->id(), str_repeat('0', 64))]),
            DestructiveMigrationPolicy::Allow,
        );
    }

    public function testCatalogAndHistoryDetachCallerLists(): void
    {
        $definitions = [$this->definition(true)];
        $catalog = new MigrationCatalog($definitions);
        $definitions[] = $this->definition(true);
        self::assertCount(1, $catalog->definitions());

        $records = [new AppliedMigration(1, 'create_records', $catalog->definitions()[0]->sha256())];
        $history = new MigrationHistory($records);
        array_pop($records);
        self::assertSame(1, $history->count());
    }

    private function definition(bool $reversible): MigrationDefinition
    {
        $table = $this->qualifiedName('app', 'records');
        $key = new SchemaColumn($this->id('record_id'), SchemaColumnType::Int64, false);
        $apply = new MigrationDirectionPlan([new CreateTable($table, [$key], [$key->name()])], MigrationRisk::Safe);
        $revert = $reversible
            ? new MigrationDirectionPlan([new DropTable($table)], MigrationRisk::Destructive)
            : null;

        return new MigrationDefinition(1, 'create_records', $apply, $revert);
    }

    private static function safeDirection(): MigrationDirectionPlan
    {
        return new MigrationDirectionPlan(
            [new CreateTable(
                new SqlQualifiedName([new SqlIdentifier('records')]),
                [new SchemaColumn(new SqlIdentifier('record_id'), SchemaColumnType::Int64, false)],
            )],
            MigrationRisk::Safe,
        );
    }

    private function id(string $value): SqlIdentifier
    {
        return new SqlIdentifier($value);
    }

    private function qualifiedName(string ...$segments): SqlQualifiedName
    {
        return new SqlQualifiedName(array_map($this->id(...), $segments));
    }
}
