<?php

declare(strict_types=1);

namespace Kongono\Database;

use Closure;

/**
 * Read-only SQL-recording connection used by `Migrator::pretend()` and `kongo migrate --pretend`.
 *
 * Captures every DDL/DML statement and binding that a migration would execute without mutating
 * the underlying database.
 */
final class PretendConnection implements ConnectionInterface
{
    /** @var list<array{sql: string, bindings: list<mixed>}> */
    private array $recorded = [];

    public function __construct(
        private readonly Dialect $dialect,
    ) {}

    public function dialect(): Dialect
    {
        return $this->dialect;
    }

    public function query(string $sql, array $bindings = []): array
    {
        return [];
    }

    public function first(string $sql, array $bindings = []): ?array
    {
        return null;
    }

    public function execute(string $sql, array $bindings = []): int
    {
        $this->recorded[] = ['sql' => $sql, 'bindings' => $bindings];

        return 0;
    }

    public function table(string $table, ?string $alias = null): QueryBuilder
    {
        return new QueryBuilder($table, $this->dialect, $this, $alias);
    }

    public function transaction(Closure $callback): mixed
    {
        return $callback($this);
    }

    /** @return list<string> */
    public function statements(): array
    {
        return array_map(static fn(array $entry): string => $entry['sql'], $this->recorded);
    }

    /** @return list<array{sql: string, bindings: list<mixed>}> */
    public function entries(): array
    {
        return $this->recorded;
    }

    public function reset(): void
    {
        $this->recorded = [];
    }
}
