<?php

declare(strict_types=1);

namespace Kongono\Database;

/** Transactional schema migrator tracking applied migrations in `kongono_migrations`. */
final readonly class Migrator
{
    public function __construct(
        private ConnectionInterface $db,
        private string $table = 'kongono_migrations',
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
     * @param list<MigrationInterface> $migrations
     * @return list<string> Applied migration IDs
     */
    public function migrate(array $migrations): array
    {
        $this->ensureTable();
        $applied = $this->appliedIds();
        $ran = [];

        foreach ($migrations as $migration) {
            if (in_array($migration->id(), $applied, true)) {
                continue;
            }
            $this->db->transaction(function (ConnectionInterface $tx) use ($migration): void {
                $migration->up($tx);
                $tx->table($this->table)->insert([
                    'id' => $migration->id(),
                    'applied_at' => gmdate(DATE_ATOM),
                ]);
            });
            $ran[] = $migration->id();
        }

        return $ran;
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
}
