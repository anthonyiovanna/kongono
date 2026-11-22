<?php

declare(strict_types=1);

namespace Kongono\Database\Schema;

use Closure;
use Kongono\Database\ConnectionInterface;
use Kongono\Database\Dialect;

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

    /**
     * Alter an existing table using dialect-portable ALTER TABLE, index, foreign key, and RLS statements.
     *
     * @param Closure(Blueprint): void $define
     */
    public static function table(ConnectionInterface $db, string $table, Closure $define): void
    {
        $blueprint = new Blueprint($table, $db->dialect());
        $define($blueprint);

        foreach ($blueprint->toAlterSqlStatements() as $sql) {
            $db->execute($sql);
        }
    }

    /** Rename an existing table across PostgreSQL, SQLite, and MySQL. */
    public static function rename(ConnectionInterface $db, string $from, string $to): void
    {
        $dialect = $db->dialect();
        $fromQuoted = $dialect->quoteIdentifier($from);
        $toQuoted = $dialect->quoteIdentifier($to);

        $sql = Dialect::Mysql === $dialect
            ? sprintf('RENAME TABLE %s TO %s', $fromQuoted, $toQuoted)
            : sprintf('ALTER TABLE %s RENAME TO %s', $fromQuoted, $toQuoted);

        $db->execute($sql);
    }

    public static function dropIfExists(ConnectionInterface $db, string $table): void
    {
        $db->execute('DROP TABLE IF EXISTS ' . $db->dialect()->quoteIdentifier($table));
    }
}
