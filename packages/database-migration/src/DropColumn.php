<?php

declare(strict_types=1);

namespace Kongono\DatabaseMigration;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseSql\SqlIdentifier;
use Kongono\DatabaseSql\SqlQualifiedName;

/** Drops one explicitly named column. */
#[Api(ApiStatus::Experimental)]
final readonly class DropColumn implements MigrationOperation
{
    public function __construct(private SqlQualifiedName $table, private SqlIdentifier $column) {}

    public function table(): SqlQualifiedName
    {
        return $this->table;
    }

    public function column(): SqlIdentifier
    {
        return $this->column;
    }

    public function kind(): string
    {
        return 'drop_column';
    }

    public function risk(): MigrationRisk
    {
        return MigrationRisk::Destructive;
    }
}
