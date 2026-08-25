<?php

declare(strict_types=1);

namespace Kongono\DatabaseMigrationPgsql;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Database\DatabaseStatement;
use Kongono\Database\ResultExpectation;
use Kongono\DatabaseMigration\AddColumn;
use Kongono\DatabaseMigration\CreateIndex;
use Kongono\DatabaseMigration\CreateTable;
use Kongono\DatabaseMigration\DropColumn;
use Kongono\DatabaseMigration\DropIndex;
use Kongono\DatabaseMigration\DropTable;
use Kongono\DatabaseMigration\Exception\MigrationExecutionException;
use Kongono\DatabaseMigration\MigrationDirection;
use Kongono\DatabaseMigration\MigrationOperation;
use Kongono\DatabaseMigration\RenameColumn;
use Kongono\DatabaseMigration\RenameTable;
use Kongono\DatabaseMigration\SchemaColumn;
use Kongono\DatabaseMigration\SchemaColumnType;
use Kongono\DatabaseMigrationPgsql\Internal\PostgreSqlMigrationSql;

/** Deterministically compiles the closed migration vocabulary to PostgreSQL 17 DDL. */
#[Api(ApiStatus::Experimental)]
final readonly class PostgreSql17MigrationCompiler
{
    private const string PROFILE = 'postgresql-17';

    /** @return list<DatabaseStatement> */
    public function compile(MigrationOperation $operation): array
    {
        $sql = match (true) {
            $operation instanceof CreateTable => $this->createTable($operation),
            $operation instanceof DropTable => 'DROP TABLE ' . PostgreSqlMigrationSql::qualified($operation->table()),
            $operation instanceof AddColumn => 'ALTER TABLE ' . PostgreSqlMigrationSql::qualified($operation->table()) . ' ADD COLUMN ' . $this->column($operation->column()),
            $operation instanceof DropColumn => 'ALTER TABLE ' . PostgreSqlMigrationSql::qualified($operation->table()) . ' DROP COLUMN ' . PostgreSqlMigrationSql::identifier($operation->column()),
            $operation instanceof CreateIndex => 'CREATE ' . ($operation->unique() ? 'UNIQUE ' : '') . 'INDEX ' . PostgreSqlMigrationSql::qualified($operation->index()) . ' ON ' . PostgreSqlMigrationSql::qualified($operation->table()) . ' (' . implode(', ', array_map(PostgreSqlMigrationSql::identifier(...), $operation->columns())) . ')',
            $operation instanceof DropIndex => 'DROP INDEX ' . PostgreSqlMigrationSql::qualified($operation->index()),
            $operation instanceof RenameTable => 'ALTER TABLE ' . PostgreSqlMigrationSql::qualified($operation->source()) . ' RENAME TO ' . PostgreSqlMigrationSql::identifier($operation->newName()),
            $operation instanceof RenameColumn => 'ALTER TABLE ' . PostgreSqlMigrationSql::qualified($operation->table()) . ' RENAME COLUMN ' . PostgreSqlMigrationSql::identifier($operation->source()) . ' TO ' . PostgreSqlMigrationSql::identifier($operation->newName()),
            default => throw MigrationExecutionException::failed(MigrationDirection::Apply, 'compile', profile: self::PROFILE),
        };

        return [new DatabaseStatement($sql, [], ResultExpectation::Completion)];
    }

    private function createTable(CreateTable $operation): string
    {
        $parts = array_map($this->column(...), $operation->columns());
        if ([] !== $operation->primaryKey()) {
            $parts[] = 'PRIMARY KEY (' . implode(', ', array_map(PostgreSqlMigrationSql::identifier(...), $operation->primaryKey())) . ')';
        }

        return 'CREATE TABLE ' . PostgreSqlMigrationSql::qualified($operation->table()) . ' (' . implode(', ', $parts) . ')';
    }

    private function column(SchemaColumn $column): string
    {
        $type = match ($column->type()) {
            SchemaColumnType::Boolean => 'BOOLEAN',
            SchemaColumnType::Int64 => 'BIGINT',
            SchemaColumnType::Float64 => 'DOUBLE PRECISION',
            SchemaColumnType::Text => 'TEXT',
            SchemaColumnType::Binary => 'BYTEA',
            SchemaColumnType::Uuid => 'UUID',
            SchemaColumnType::Json => 'JSONB',
            SchemaColumnType::TimestampTz => 'TIMESTAMPTZ',
        };

        return PostgreSqlMigrationSql::identifier($column->name()) . ' ' . $type . ($column->nullable() ? '' : ' NOT NULL');
    }
}
