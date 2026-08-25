<?php

declare(strict_types=1);

namespace Kongono\DatabaseMigrationPgsql\Tests;

use Kongono\DatabaseMigration\AddColumn;
use Kongono\DatabaseMigration\CreateIndex;
use Kongono\DatabaseMigration\CreateTable;
use Kongono\DatabaseMigration\DropColumn;
use Kongono\DatabaseMigration\DropIndex;
use Kongono\DatabaseMigration\DropTable;
use Kongono\DatabaseMigration\Exception\MigrationExecutionException;
use Kongono\DatabaseMigration\MigrationOperation;
use Kongono\DatabaseMigration\RenameColumn;
use Kongono\DatabaseMigration\RenameTable;
use Kongono\DatabaseMigration\SchemaColumn;
use Kongono\DatabaseMigration\SchemaColumnType;
use Kongono\DatabaseMigrationPgsql\PostgreSql17MigrationCompiler;
use Kongono\DatabaseMigrationPgsql\PostgreSqlMigrationConfiguration;
use Kongono\DatabaseSql\SqlIdentifier;
use Kongono\DatabaseSql\SqlQualifiedName;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PostgreSql17MigrationCompilerTest extends TestCase
{
    #[DataProvider('compilationProvider')]
    public function testCompilerEmitsExactPostgreSqlSeventeenDdl(MigrationOperation $operation, string $expected): void
    {
        $statement = (new PostgreSql17MigrationCompiler())->compile($operation)[0];
        self::assertSame($expected, $statement->sql());
        self::assertSame([], $statement->parameters());
    }

    /** @return iterable<string, array{MigrationOperation, string}> */
    public static function compilationProvider(): iterable
    {
        $table = self::qualifiedName('app', 'records');
        $index = self::qualifiedName('app', 'records_payload_idx');
        $key = new SchemaColumn(self::id('record_id'), SchemaColumnType::Int64, false);
        $payload = new SchemaColumn(self::id('pay"load'), SchemaColumnType::Json, true);

        yield 'create table' => [
            new CreateTable($table, [$key, $payload], [$key->name()]),
            'CREATE TABLE "app"."records" ("record_id" BIGINT NOT NULL, "pay""load" JSONB, PRIMARY KEY ("record_id"))',
        ];
        yield 'drop table' => [new DropTable($table), 'DROP TABLE "app"."records"'];
        yield 'add column' => [new AddColumn($table, $payload), 'ALTER TABLE "app"."records" ADD COLUMN "pay""load" JSONB'];
        yield 'drop column' => [new DropColumn($table, $payload->name()), 'ALTER TABLE "app"."records" DROP COLUMN "pay""load"'];
        yield 'create unique index' => [new CreateIndex($index, $table, [$payload->name()], true), 'CREATE UNIQUE INDEX "app"."records_payload_idx" ON "app"."records" ("pay""load")'];
        yield 'drop index' => [new DropIndex($index), 'DROP INDEX "app"."records_payload_idx"'];
        yield 'rename table' => [new RenameTable($table, self::id('renamed')), 'ALTER TABLE "app"."records" RENAME TO "renamed"'];
        yield 'rename column' => [new RenameColumn($table, $payload->name(), self::id('renamed')), 'ALTER TABLE "app"."records" RENAME COLUMN "pay""load" TO "renamed"'];
    }

    public function testEveryColumnTypeUsesTheExactProfileMapping(): void
    {
        $types = [
            [SchemaColumnType::Boolean, 'BOOLEAN'],
            [SchemaColumnType::Int64, 'BIGINT'],
            [SchemaColumnType::Float64, 'DOUBLE PRECISION'],
            [SchemaColumnType::Text, 'TEXT'],
            [SchemaColumnType::Binary, 'BYTEA'],
            [SchemaColumnType::Uuid, 'UUID'],
            [SchemaColumnType::Json, 'JSONB'],
            [SchemaColumnType::TimestampTz, 'TIMESTAMPTZ'],
        ];
        foreach ($types as [$type, $sqlType]) {
            $statement = (new PostgreSql17MigrationCompiler())->compile(new CreateTable(
                self::qualifiedName('records'),
                [new SchemaColumn(self::id('value'), $type, true)],
            ))[0];
            self::assertSame('CREATE TABLE "records" ("value" ' . $sqlType . ')', $statement->sql());
        }
    }

    public function testPostgreSqlIdentifierBoundFailsClosedWithoutDisclosure(): void
    {
        try {
            (new PostgreSql17MigrationCompiler())->compile(new DropTable(self::qualifiedName(str_repeat('x', 64))));
            self::fail('Expected profile-bound failure.');
        } catch (MigrationExecutionException $failure) {
            self::assertStringNotContainsString(str_repeat('x', 64), (string) $failure);
        }
    }

    public function testConfigurationRetainsExplicitValuesWithoutStringSurface(): void
    {
        $name = self::qualifiedName('infra', 'history');
        $configuration = new PostgreSqlMigrationConfiguration($name, PHP_INT_MIN);
        self::assertSame($name, $configuration->historyTable());
        self::assertSame(PHP_INT_MIN, $configuration->advisoryLockKey());
        self::assertNotContains('__toString', get_class_methods($configuration));
    }

    private static function id(string $value): SqlIdentifier
    {
        return new SqlIdentifier($value);
    }

    private static function qualifiedName(string ...$segments): SqlQualifiedName
    {
        return new SqlQualifiedName(array_map(self::id(...), $segments));
    }
}
