<?php

declare(strict_types=1);

namespace Kongono\Database\Testing;

use Closure;
use Kongono\Database\ConnectionInterface;
use Kongono\Database\DatabaseException;
use RuntimeException;

/**
 * Deterministic database testing utilities for SQLite, PostgreSQL, and MySQL:
 *   - Transaction-rollback test isolation (`inRollbackTransaction()`, safe with nested savepoints)
 *   - Expressive row-existence & count assertions (`assertDatabaseHas`, `assertDatabaseMissing`, `assertDatabaseCount`)
 */
final readonly class DatabaseTestHelper
{
    public function __construct(
        private ConnectionInterface $db,
    ) {}

    /**
     * Execute `$callback` inside a real database transaction and ALWAYS roll it back
     * on completion so test mutations never leak to subsequent tests.
     *
     * @template T
     * @param Closure(ConnectionInterface): T $callback
     * @return T
     */
    public function inRollbackTransaction(Closure $callback): mixed
    {
        $sentinel = new class ('kongono_test_rollback') extends RuntimeException {};
        $captured = null;

        try {
            $this->db->transaction(static function (ConnectionInterface $tx) use ($callback, $sentinel, &$captured): never {
                $captured = $callback($tx);
                throw $sentinel;
            });
        } catch (RuntimeException $e) {
            if ($e !== $sentinel) {
                throw $e;
            }
        }

        /** @var T $captured */
        return $captured;
    }

    /**
     * Assert that at least one row exists in `$table` matching all `$criteria` columns.
     *
     * @param array<string, mixed> $criteria
     */
    public function assertDatabaseHas(string $table, array $criteria): self
    {
        $count = $this->countMatching($table, $criteria);
        if ($count < 1) {
            throw new DatabaseException(sprintf(
                'Failed asserting that table "%s" contains a row matching %s.',
                $table,
                self::formatCriteria($criteria),
            ));
        }

        return $this;
    }

    /**
     * Assert that ZERO rows exist in `$table` matching all `$criteria` columns.
     *
     * @param array<string, mixed> $criteria
     */
    public function assertDatabaseMissing(string $table, array $criteria): self
    {
        $count = $this->countMatching($table, $criteria);
        if ($count > 0) {
            throw new DatabaseException(sprintf(
                'Failed asserting that table "%s" is missing rows matching %s (found %d).',
                $table,
                self::formatCriteria($criteria),
                $count,
            ));
        }

        return $this;
    }

    /**
     * Assert that `$table` contains exactly `$expected` rows (optionally filtered by `$criteria`).
     *
     * @param array<string, mixed> $criteria
     */
    public function assertDatabaseCount(string $table, int $expected, array $criteria = []): self
    {
        $actual = $this->countMatching($table, $criteria);
        if ($actual !== $expected) {
            throw new DatabaseException(sprintf(
                'Failed asserting that table "%s" has %d row(s)%s, found %d.',
                $table,
                $expected,
                [] !== $criteria ? ' matching ' . self::formatCriteria($criteria) : '',
                $actual,
            ));
        }

        return $this;
    }

    /** @param array<string, mixed> $criteria */
    private function countMatching(string $table, array $criteria): int
    {
        $query = $this->db->table($table);
        foreach ($criteria as $column => $value) {
            if (null === $value) {
                $query->whereNull($column);
            } else {
                $query->where($column, '=', $value);
            }
        }

        return $query->count();
    }

    /** @param array<string, mixed> $criteria */
    private static function formatCriteria(array $criteria): string
    {
        try {
            return json_encode($criteria, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        } catch (\JsonException) {
            return var_export($criteria, true);
        }
    }
}
