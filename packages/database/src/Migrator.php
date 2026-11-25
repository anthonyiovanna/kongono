<?php

declare(strict_types=1);

namespace Kongono\Database;

use Throwable;

/** Transactional schema migrator tracking applied migrations in `kongono_migrations`. */
final readonly class Migrator
{
    public function __construct(
        private ConnectionInterface $db,
        private string $table = 'kongono_migrations',
        private bool $useAdvisoryLock = true,
    ) {}

    public function ensureTable(): void
    {
        $quoted = $this->db->dialect()->quoteIdentifier($this->table);
        $this->db->execute(sprintf(
            'CREATE TABLE IF NOT EXISTS %s (id VARCHAR(255) PRIMARY KEY, applied_at VARCHAR(64) NOT NULL)',
            $quoted,
        ));
    }

    /**
     * Run all pending migrations in order.
     *
     * Standard migrations execute atomically inside a transaction. Migrations implementing
     * {@see NonTransactionalMigrationInterface} (for example PostgreSQL `CREATE INDEX CONCURRENTLY`)
     * execute outside a transaction block and then record their ID.
     *
     * @param list<MigrationInterface> $migrations
     * @return list<string> Applied migration IDs
     */
    public function migrate(array $migrations): array
    {
        return $this->withMigrationLock(function () use ($migrations): array {
            $this->ensureTable();
            $applied = $this->appliedIds();
            $ran = [];

            foreach ($migrations as $migration) {
                if (in_array($migration->id(), $applied, true)) {
                    continue;
                }
                if ($migration instanceof NonTransactionalMigrationInterface) {
                    $migration->up($this->db);
                    $this->db->table($this->table)->insert([
                        'id' => $migration->id(),
                        'applied_at' => gmdate(DATE_ATOM),
                    ]);
                } else {
                    $this->db->transaction(function (ConnectionInterface $tx) use ($migration): void {
                        $migration->up($tx);
                        $tx->table($this->table)->insert([
                            'id' => $migration->id(),
                            'applied_at' => gmdate(DATE_ATOM),
                        ]);
                    });
                }
                $ran[] = $migration->id();
            }

            return $ran;
        });
    }

    /**
     * Dry-run all pending migrations against a read-only recording connection and return the exact
     * compiled SQL statements each pending migration would execute, without mutating the database.
     *
     * @param list<MigrationInterface> $migrations
     * @return array<string, list<string>> Map of pending migration ID => SQL statements
     */
    public function pretend(array $migrations): array
    {
        try {
            $applied = $this->appliedIds();
        } catch (Throwable) {
            // Migrations tracking table may not exist yet on a fresh database; remain strictly read-only.
            $applied = [];
        }

        $preview = [];
        foreach ($migrations as $migration) {
            if (in_array($migration->id(), $applied, true)) {
                continue;
            }
            $recorder = new PretendConnection($this->db->dialect());
            $migration->up($recorder);
            $preview[$migration->id()] = $recorder->statements();
        }

        return $preview;
    }

    /**
     * Drop all user tables in the active schema/database and re-run every migration from scratch.
     *
     * @param list<MigrationInterface> $migrations
     * @return array{dropped: list<string>, applied: list<string>}
     */
    public function fresh(array $migrations): array
    {
        $dropped = $this->dropAllTables();
        $applied = $this->migrate($migrations);

        return [
            'dropped' => $dropped,
            'applied' => $applied,
        ];
    }

    /**
     * Drop every user table in the current schema/database cleanly without destroying extensions
     * (such as PostgreSQL `pgvector`).
     *
     * @return list<string> Dropped table names
     */
    public function dropAllTables(): array
    {
        $dialect = $this->db->dialect();

        if (Dialect::Postgres === $dialect) {
            $rows = $this->db->query(
                'SELECT tablename AS name FROM pg_tables WHERE schemaname = current_schema() ORDER BY tablename',
            );
            $tables = $this->extractTableNames($rows);
            foreach ($tables as $table) {
                $this->db->execute(sprintf('DROP TABLE IF EXISTS %s CASCADE', $dialect->quoteIdentifier($table)));
            }

            return $tables;
        }

        if (Dialect::Sqlite === $dialect) {
            $rows = $this->db->query(
                "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name",
            );
            $tables = $this->extractTableNames($rows);
            $this->db->execute('PRAGMA foreign_keys = OFF');
            try {
                foreach ($tables as $table) {
                    $this->db->execute('DROP TABLE IF EXISTS ' . $dialect->quoteIdentifier($table));
                }
            } finally {
                $this->db->execute('PRAGMA foreign_keys = ON');
            }

            return $tables;
        }

        $rows = $this->db->query(
            "SELECT TABLE_NAME AS name FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME",
        );
        $tables = $this->extractTableNames($rows);
        $this->db->execute('SET FOREIGN_KEY_CHECKS = 0');
        try {
            foreach ($tables as $table) {
                $this->db->execute('DROP TABLE IF EXISTS ' . $dialect->quoteIdentifier($table));
            }
        } finally {
            $this->db->execute('SET FOREIGN_KEY_CHECKS = 1');
        }

        return $tables;
    }

    /**
     * Roll back the last $steps applied migrations in reverse order inside strict transactions.
     *
     * @param list<MigrationInterface> $migrations
     * @return list<string> Rolled-back migration IDs
     */
    public function rollback(array $migrations, int $steps = 1): array
    {
        if ($steps < 1) {
            throw new DatabaseException(sprintf('Rollback steps must be at least 1 (got %d).', $steps));
        }

        return $this->withMigrationLock(function () use ($migrations, $steps): array {
            $this->ensureTable();
            $applied = $this->appliedIds();
            if ([] === $applied) {
                return [];
            }

            $byId = [];
            foreach ($migrations as $migration) {
                $byId[$migration->id()] = $migration;
            }

            $targets = array_slice(array_reverse($applied), 0, $steps);
            $rolledBack = [];

            foreach ($targets as $id) {
                if (!isset($byId[$id])) {
                    continue;
                }
                $migration = $byId[$id];
                if ($migration instanceof NonTransactionalMigrationInterface) {
                    $migration->down($this->db);
                    $this->db->table($this->table)->where('id', '=', $id)->delete();
                } else {
                    $this->db->transaction(function (ConnectionInterface $tx) use ($migration, $id): void {
                        $migration->down($tx);
                        $tx->table($this->table)->where('id', '=', $id)->delete();
                    });
                }
                $rolledBack[] = $id;
            }

            return $rolledBack;
        });
    }

    /**
     * Serialize concurrent migration runs across multi-pod deploys using native DB advisory locks on PDO connections.
     *
     * @template T
     * @param \Closure(): T $callback
     * @return T
     */
    private function withMigrationLock(\Closure $callback): mixed
    {
        if (!$this->useAdvisoryLock) {
            return $callback();
        }

        $target = $this->db instanceof ReadWriteConnection ? $this->db->writeConnection() : $this->db;
        if (!$target instanceof PdoConnection) {
            return $callback();
        }

        $lockKey = 'kongono_migrations:' . $this->table;
        $dialect = $target->dialect();

        if (Dialect::Postgres === $dialect) {
            $target->first('SELECT pg_advisory_lock(hashtext(?))', [$lockKey]);
            try {
                return $callback();
            } finally {
                try {
                    $target->first('SELECT pg_advisory_unlock(hashtext(?))', [$lockKey]);
                } catch (Throwable) {
                    // Best-effort unlock; session teardown also releases PostgreSQL advisory locks
                }
            }
        }

        if (Dialect::Mysql === $dialect) {
            $row = $target->first('SELECT GET_LOCK(?, 30) AS acquired', [$lockKey]);
            $acquired = is_array($row) && is_numeric($row['acquired'] ?? null) ? (int) $row['acquired'] : 0;
            if (1 !== $acquired) {
                throw new DatabaseException('Unable to acquire MySQL migration advisory lock within 30 seconds.');
            }
            try {
                return $callback();
            } finally {
                try {
                    $target->first('SELECT RELEASE_LOCK(?)', [$lockKey]);
                } catch (Throwable) {
                    // Best-effort unlock
                }
            }
        }

        return $callback();
    }

    /**
     * Return applied/pending status for every known migration in order.
     *
     * @param list<MigrationInterface> $migrations
     * @return list<array{id: string, applied: bool}>
     */
    public function status(array $migrations): array
    {
        $this->ensureTable();
        $applied = $this->appliedIds();
        $rows = [];

        foreach ($migrations as $migration) {
            $id = $migration->id();
            $rows[] = [
                'id' => $id,
                'applied' => in_array($id, $applied, true),
            ];
        }

        return $rows;
    }

    /** @return list<string> */
    public function appliedIds(): array
    {
        $rows = $this->db->table($this->table)->select('id')->orderBy('id')->get();
        $ids = [];
        foreach ($rows as $row) {
            if (isset($row['id']) && is_string($row['id'])) {
                $ids[] = $row['id'];
            }
        }

        return $ids;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<string>
     */
    private function extractTableNames(array $rows): array
    {
        $tables = [];
        foreach ($rows as $row) {
            $name = $row['name'] ?? null;
            if (is_string($name) && '' !== $name) {
                $tables[] = $name;
            }
        }

        return $tables;
    }
}
