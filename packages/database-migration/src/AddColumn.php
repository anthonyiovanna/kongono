<?php

declare(strict_types=1);

namespace Kongono\DatabaseMigration;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseMigration\Exception\InvalidMigrationDefinitionException;
use Kongono\DatabaseSql\SqlQualifiedName;

/** Adds one nullable column without a default or backfill contract. */
#[Api(ApiStatus::Experimental)]
final readonly class AddColumn implements MigrationOperation
{
    public function __construct(private SqlQualifiedName $table, private SchemaColumn $column)
    {
        if (!$column->nullable()) {
            throw InvalidMigrationDefinitionException::invalidDefinition();
        }
    }

    public function table(): SqlQualifiedName
    {
        return $this->table;
    }

    public function column(): SchemaColumn
    {
        return $this->column;
    }

    public function kind(): string
    {
        return 'add_column';
    }

    public function risk(): MigrationRisk
    {
        return MigrationRisk::Safe;
    }
}
