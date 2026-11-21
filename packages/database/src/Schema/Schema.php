<?php

declare(strict_types=1);

namespace Kongono\Database\Schema;

use Closure;
use Kongono\Database\ConnectionInterface;

/** Expressive schema builder for migrations (PostgreSQL, SQLite, MySQL). */
final class Schema
{
    /** @param Closure(Blueprint): void $define */
    public static function create(ConnectionInterface $db, string $table, Closure $define): void
    {
        $blueprint = new Blueprint($table, $db->dialect());
        $define($blueprint);

        foreach ($blueprint->toSqlStatements() as $sql) {
            $db->execute($sql);
        }
    }

    public static function dropIfExists(ConnectionInterface $db, string $table): void
    {
        $db->execute('DROP TABLE IF EXISTS ' . $db->dialect()->quoteIdentifier($table));
    }
}
