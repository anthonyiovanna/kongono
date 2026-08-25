<?php

declare(strict_types=1);

namespace Kongono\DatabaseMigration;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseSql\SqlIdentifier;
use Kongono\DatabaseSql\SqlQualifiedName;

/** Renames one table within its existing qualification scope. */
#[Api(ApiStatus::Experimental)]
final readonly class RenameTable implements MigrationOperation
{
    public function __construct(private SqlQualifiedName $source, private SqlIdentifier $newName) {}

    public function source(): SqlQualifiedName
    {
        return $this->source;
    }

    public function newName(): SqlIdentifier
    {
        return $this->newName;
    }

    public function kind(): string
    {
        return 'rename_table';
    }

    public function risk(): MigrationRisk
    {
        return MigrationRisk::Destructive;
    }
}
