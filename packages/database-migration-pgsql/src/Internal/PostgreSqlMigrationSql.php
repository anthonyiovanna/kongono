<?php

declare(strict_types=1);

namespace Kongono\DatabaseMigrationPgsql\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseMigration\Exception\MigrationExecutionException;
use Kongono\DatabaseSql\SqlIdentifier;
use Kongono\DatabaseSql\SqlQualifiedName;

/** Owns PostgreSQL 17 identifier quoting and fixed history statements. */
#[Api(ApiStatus::Internal)]
final class PostgreSqlMigrationSql
{
    public static function identifier(SqlIdentifier $identifier): string
    {
        $value = $identifier->value();
        if (strlen($value) > 63) {
            throw MigrationExecutionException::failed(
                \Kongono\DatabaseMigration\MigrationDirection::Apply,
                'compile',
                profile: 'postgresql-17',
            );
        }

        return '"' . str_replace('"', '""', $value) . '"';
    }

    public static function qualified(SqlQualifiedName $name): string
    {
        return implode('.', array_map(self::identifier(...), $name->segments()));
    }

    private function __construct() {}
}
