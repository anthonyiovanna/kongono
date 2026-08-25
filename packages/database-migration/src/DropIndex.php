<?php

declare(strict_types=1);

namespace Kongono\DatabaseMigration;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseSql\SqlQualifiedName;

/** Drops one explicitly named index. */
#[Api(ApiStatus::Experimental)]
final readonly class DropIndex implements MigrationOperation
{
    public function __construct(private SqlQualifiedName $index) {}

    public function index(): SqlQualifiedName
    {
        return $this->index;
    }

    public function kind(): string
    {
        return 'drop_index';
    }

    public function risk(): MigrationRisk
    {
        return MigrationRisk::Destructive;
    }
}
