<?php

declare(strict_types=1);

namespace Kongono\Database;

use Closure;
use Kongono\Core\Capabilities\ExecutionCapabilities;
use Kongono\Core\Capabilities\QueryGuardableInterface;

/**
 * Primary/read-replica database connection router with fail-safe primary fallback,
 * round-robin replica selection, scoped SQL capability guard propagation, and
 * persistent-runtime-safe sticky-after-write semantics.
 */
final class ReadWriteConnection implements ConnectionInterface, QueryGuardableInterface
{
    private int $replicaIndex = 0;
    private bool $hasWritten = false;
    private int $transactionDepth = 0;

    /** @var array<int, Closure(string): void> */
    private array $localGuards = [];

    private int $nextGuardId = 0;

    /**
     * @param list<ConnectionInterface> $readReplicas
     */
    public function __construct(
        private readonly ConnectionInterface $write,
        private readonly array $readReplicas = [],
        private readonly bool $stickyAfterWrite = true,
    ) {}

    public function guardBeforeQuery(Closure $guard): Closure
    {
        $id = ++$this->nextGuardId;
        $this->localGuards[$id] = $guard;

        /** @var list<Closure(): void> $childCleanups */
        $childCleanups = [];
        $all = [$this->write, ...$this->readReplicas];
        /** @var list<object> $seen */
        $seen = [];
        foreach ($all as $conn) {
            if ($conn instanceof QueryGuardableInterface && !in_array($conn, $seen, true)) {
                $seen[] = $conn;
                $childCleanups[] = $conn->guardBeforeQuery($guard);
            }
        }

        return function () use ($id, $childCleanups): void {
            unset($this->localGuards[$id]);
            for ($i = count($childCleanups) - 1; $i >= 0; --$i) {
                ($childCleanups[$i])();
            }
        };
    }

    public function dialect(): Dialect
    {
        return $this->write->dialect();
    }

    public function inTransaction(): bool
    {
        return $this->transactionDepth > 0
            || ($this->write instanceof PdoConnection && $this->write->inTransaction());
    }

    public function query(string $sql, array $bindings = []): array
    {
        if (self::requiresPrimary($sql)) {
            $this->assertLocalGuardsIfNeeded($this->write, $sql);
            if (self::isMutatingSql($sql)) {
                $this->hasWritten = true;
            }

            return $this->write->query($sql, $bindings);
        }

        $conn = $this->selectReadConnection();
        $this->assertLocalGuardsIfNeeded($conn, $sql);

        return $conn->query($sql, $bindings);
    }

    public function first(string $sql, array $bindings = []): ?array
    {
        if (self::requiresPrimary($sql)) {
            $this->assertLocalGuardsIfNeeded($this->write, $sql);
            if (self::isMutatingSql($sql)) {
                $this->hasWritten = true;
            }

            return $this->write->first($sql, $bindings);
        }

        $conn = $this->selectReadConnection();
        $this->assertLocalGuardsIfNeeded($conn, $sql);

        return $conn->first($sql, $bindings);
    }

    public function execute(string $sql, array $bindings = []): int
    {
        $this->assertLocalGuardsIfNeeded($this->write, $sql);
        $this->hasWritten = true;

        return $this->write->execute($sql, $bindings);
    }

    private function assertLocalGuardsIfNeeded(ConnectionInterface $target, string $sql): void
    {
        if ($target instanceof QueryGuardableInterface || [] === $this->localGuards) {
            return;
        }
        foreach ($this->localGuards as $guard) {
            $guard($sql);
        }
    }

    public function table(string $table, ?string $alias = null): QueryBuilder
    {
        return new QueryBuilder($table, $this->dialect(), $this, $alias);
    }

    public function transaction(Closure $callback): mixed
    {
        $this->hasWritten = true;
        ++$this->transactionDepth;

        try {
            return $this->write->transaction(function (ConnectionInterface $tx) use ($callback): mixed {
                if ($tx instanceof QueryGuardableInterface || [] === $this->localGuards) {
                    return $callback($tx);
                }

                $guarded = $this->wrapGuardedTransactionConnection($tx);

                return $callback($guarded);
            });
        } finally {
            --$this->transactionDepth;
        }
    }

    private function wrapGuardedTransactionConnection(ConnectionInterface $tx): ConnectionInterface
    {
        $check = function (string $sql): void {
            foreach ($this->localGuards as $guard) {
                $guard($sql);
            }
        };

        return new readonly class ($tx, $check) implements ConnectionInterface {
            /** @param Closure(string): void $check */
            public function __construct(
                private ConnectionInterface $inner,
                private Closure $check,
            ) {}

            public function dialect(): Dialect
            {
                return $this->inner->dialect();
            }

            public function query(string $sql, array $bindings = []): array
            {
                ($this->check)($sql);

                return $this->inner->query($sql, $bindings);
            }

            public function first(string $sql, array $bindings = []): ?array
            {
                ($this->check)($sql);

                return $this->inner->first($sql, $bindings);
            }

            public function execute(string $sql, array $bindings = []): int
            {
                ($this->check)($sql);

                return $this->inner->execute($sql, $bindings);
            }

            public function table(string $table, ?string $alias = null): QueryBuilder
            {
                return new QueryBuilder($table, $this->inner->dialect(), $this, $alias);
            }

            public function transaction(Closure $callback): mixed
            {
                $check = $this->check;

                return $this->inner->transaction(static function (ConnectionInterface $nested) use ($callback, $check): mixed {
                    return $callback(new self($nested, $check));
                });
            }
        };
    }

    /** Reset sticky-after-write state at the end of a persistent-worker request scope. */
    public function resetSticky(): void
    {
        $this->hasWritten = false;
    }

    public function isSticky(): bool
    {
        return $this->hasWritten;
    }

    public function writeConnection(): ConnectionInterface
    {
        return $this->write;
    }

    /** @return list<ConnectionInterface> */
    public function readConnections(): array
    {
        return $this->readReplicas;
    }

    private function selectReadConnection(): ConnectionInterface
    {
        if (
            [] === $this->readReplicas
            || $this->inTransaction()
            || ($this->stickyAfterWrite && $this->hasWritten)
        ) {
            return $this->write;
        }

        $count = count($this->readReplicas);
        $replica = $this->readReplicas[$this->replicaIndex % $count];
        ++$this->replicaIndex;

        return $replica;
    }

    private static function stripLeadingComments(string $sql): string
    {
        $trimmed = ltrim($sql);
        while ('' !== $trimmed) {
            if (str_starts_with($trimmed, '--')) {
                $nl = strpos($trimmed, "\n");
                $trimmed = false === $nl ? '' : ltrim(substr($trimmed, $nl + 1));
                continue;
            }
            if (str_starts_with($trimmed, '/*')) {
                $end = strpos($trimmed, '*/', 2);
                if (false === $end) {
                    return '';
                }
                $trimmed = ltrim(substr($trimmed, $end + 2));
                continue;
            }
            break;
        }

        return $trimmed;
    }

    private static function isMutatingSql(string $sql): bool
    {
        $clean = self::stripLeadingComments($sql);
        if ('' === $clean) {
            return false;
        }

        // Ignore row-locking clauses (`FOR UPDATE`, `FOR NO KEY UPDATE`) which require primary without mutating state
        $withoutLocks = preg_replace('/\bFOR\s+(?:NO\s+KEY\s+)?(?:UPDATE|SHARE)\b/i', '', $clean);
        $subject = is_string($withoutLocks) ? $withoutLocks : $clean;

        return ExecutionCapabilities::isWriteSql($subject);
    }

    private static function requiresPrimary(string $sql): bool
    {
        $clean = self::stripLeadingComments($sql);
        if ('' === $clean) {
            return true;
        }

        // Only pure SELECT / read-only WITH queries without row locks or session/sequence/lock functions may use read replicas
        if (1 !== preg_match('/\A(?:SELECT|WITH)\b/i', $clean)) {
            return true;
        }

        if (ExecutionCapabilities::isWriteSql($clean)) {
            return true;
        }

        return 1 === preg_match('/\b(?:LAST_INSERT_ID|FOUND_ROWS|ROW_COUNT)\s*\(/i', $clean);
    }
}
