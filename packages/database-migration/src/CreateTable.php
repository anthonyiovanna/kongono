<?php

declare(strict_types=1);

namespace Kongono\DatabaseMigration;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseMigration\Exception\InvalidMigrationDefinitionException;
use Kongono\DatabaseSql\SqlIdentifier;
use Kongono\DatabaseSql\SqlQualifiedName;

/** Creates one table from explicit ordered columns and primary-key membership. */
#[Api(ApiStatus::Experimental)]
final readonly class CreateTable implements MigrationOperation
{
    /** @var list<SchemaColumn> */
    private array $columns;

    /** @var list<SqlIdentifier> */
    private array $primaryKey;

    /**
     * @param array<array-key, mixed> $columns
     * @param array<array-key, mixed> $primaryKey
     */
    public function __construct(private SqlQualifiedName $table, array $columns, array $primaryKey = [])
    {
        if (!array_is_list($columns) || [] === $columns || count($columns) > 4_096 || !array_is_list($primaryKey)) {
            throw InvalidMigrationDefinitionException::invalidDefinition();
        }
        $columnMap = [];
        $detachedColumns = [];
        foreach ($columns as $column) {
            if (!$column instanceof SchemaColumn || isset($columnMap[$column->name()->value()])) {
                throw InvalidMigrationDefinitionException::invalidDefinition();
            }
            $columnMap[$column->name()->value()] = $column;
            $detachedColumns[] = $column;
        }
        $detachedPrimaryKey = [];
        $seenPrimaryKey = [];
        foreach ($primaryKey as $identifier) {
            if (!$identifier instanceof SqlIdentifier || isset($seenPrimaryKey[$identifier->value()])) {
                throw InvalidMigrationDefinitionException::invalidDefinition();
            }
            $column = $columnMap[$identifier->value()] ?? null;
            if (!$column instanceof SchemaColumn || $column->nullable()) {
                throw InvalidMigrationDefinitionException::invalidDefinition();
            }
            $seenPrimaryKey[$identifier->value()] = true;
            $detachedPrimaryKey[] = $identifier;
        }
        $this->columns = $detachedColumns;
        $this->primaryKey = $detachedPrimaryKey;
    }

    public function table(): SqlQualifiedName
    {
        return $this->table;
    }

    /** @return list<SchemaColumn> */
    public function columns(): array
    {
        return [...$this->columns];
    }

    /** @return list<SqlIdentifier> */
    public function primaryKey(): array
    {
        return [...$this->primaryKey];
    }

    public function kind(): string
    {
        return 'create_table';
    }

    public function risk(): MigrationRisk
    {
        return MigrationRisk::Safe;
    }
}
