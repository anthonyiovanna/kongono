<?php

declare(strict_types=1);

namespace Kongono\DatabaseMigrationPgsql;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseSql\SqlQualifiedName;

/** Holds explicit protected PostgreSQL migration infrastructure configuration. */
#[Api(ApiStatus::Experimental)]
final readonly class PostgreSqlMigrationConfiguration
{
    public function __construct(private SqlQualifiedName $historyTable, private int $advisoryLockKey) {}

    public function historyTable(): SqlQualifiedName
    {
        return $this->historyTable;
    }

    public function advisoryLockKey(): int
    {
        return $this->advisoryLockKey;
    }
}
