<?php

declare(strict_types=1);

namespace Kongono\Database;

use Closure;

/** Explicit database connection boundary (zero globals, strict transactions). */
interface ConnectionInterface
{
    public function dialect(): Dialect;

    /**
     * @param list<mixed> $bindings
     * @return list<array<string, mixed>>
     */
    public function query(string $sql, array $bindings = []): array;

    /**
     * @param list<mixed> $bindings
     * @return array<string, mixed>|null
     */
    public function first(string $sql, array $bindings = []): ?array;

    /** @param list<mixed> $bindings */
    public function execute(string $sql, array $bindings = []): int;

    public function table(string $table, ?string $alias = null): QueryBuilder;

    /**
     * @template T
     * @param Closure(ConnectionInterface): T $callback
     * @return T
     */
    public function transaction(Closure $callback): mixed;
}
