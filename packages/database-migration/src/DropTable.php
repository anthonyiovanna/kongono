<?php

declare(strict_types=1);

namespace Kongono\DatabaseMigration;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseSql\SqlQualifiedName;

/** Drops one explicitly named table. */
#[Api(ApiStatus::Experimental)]
final readonly class DropTable implements MigrationOperation
{
    public function __construct(private SqlQualifiedName $table) {}

    public function table(): SqlQualifiedName
    {
        return $this->table;
    }

    public function kind(): string
    {
        return 'drop_table';
    }

    public function risk(): MigrationRisk
    {
        return MigrationRisk::Destructive;
    }
}
