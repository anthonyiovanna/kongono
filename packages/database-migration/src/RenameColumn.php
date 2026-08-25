<?php

declare(strict_types=1);

namespace Kongono\DatabaseMigration;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseSql\SqlIdentifier;
use Kongono\DatabaseSql\SqlQualifiedName;

/** Renames one column without changing table ownership. */
#[Api(ApiStatus::Experimental)]
final readonly class RenameColumn implements MigrationOperation
{
    public function __construct(
        private SqlQualifiedName $table,
        private SqlIdentifier $source,
        private SqlIdentifier $newName,
    ) {}

    public function table(): SqlQualifiedName
    {
        return $this->table;
    }

    public function source(): SqlIdentifier
    {
        return $this->source;
    }

    public function newName(): SqlIdentifier
    {
        return $this->newName;
    }

    public function kind(): string
    {
        return 'rename_column';
    }

    public function risk(): MigrationRisk
    {
        return MigrationRisk::Destructive;
    }
}
