<?php

declare(strict_types=1);

namespace Kongono\Database;

use Closure;

/**
 * Primary/read-replica database connection router with fail-safe primary fallback,
 * round-robin replica selection, and persistent-runtime-safe sticky-after-write semantics.
 */
final class ReadWriteConnection implements ConnectionInterface
{
    private int $replicaIndex = 0;
    private bool $hasWritten = false;
    private int $transactionDepth = 0;

    /**
     * @param list<ConnectionInterface> $readReplicas
     */
    public function __construct(
        private readonly ConnectionInterface $write,
        private readonly array $readReplicas = [],
        private readonly bool $stickyAfterWrite = true,
    ) {}

    public function dialect(): Dialect
    {
        return $this->write->dialect();
    }

    public function query(string $sql, array $bindings = []): array
    {
        if (self::requiresPrimary($sql)) {
            if (self::isMutatingSql($sql)) {
                $this->hasWritten = true;
            }

            return $this->write->query($sql, $bindings);
        }

        return $this->selectReadConnection()->query($sql, $bindings);
    }

    public function first(string $sql, array $bindings = []): ?array
    {
        $rows = $this->query($sql, $bindings);

        return $rows[0] ?? null;
    }

    public function execute(string $sql, array $bindings = []): int
    {
        $this->hasWritten = true;

        return $this->write->execute($sql, $bindings);
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
            return $this->write->transaction(static fn(ConnectionInterface $tx): mixed => $callback($tx));
        } finally {
            --$this->transactionDepth;
        }
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
            || $this->transactionDepth > 0
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
        $withoutLocks = preg_replace('/\bFOR\s+(?:NO\s+KEY\s+)?UPDATE\b/i', '', $clean);
        $subject = is_string($withoutLocks) ? $withoutLocks : $clean;

        return 1 === preg_match('/\b(?:INSERT|UPDATE|DELETE|MERGE|ALTER|CREATE|DROP|TRUNCATE|GRANT|REVOKE|LOCK\s+TABLE)\b/i', $subject);
    }

    private static function requiresPrimary(string $sql): bool
    {
        $clean = self::stripLeadingComments($sql);
        if ('' === $clean) {
            return true;
        }

        // Only pure SELECT / read-only WITH queries without row locks may use read replicas
        if (1 !== preg_match('/\A(?:SELECT|WITH)\b/i', $clean)) {
            return true;
        }

        if (self::isMutatingSql($clean)) {
            return true;
        }

        return 1 === preg_match('/\bFOR\s+(?:UPDATE|SHARE|NO\s+KEY\s+UPDATE|KEY\s+SHARE)\b/i', $clean)
            || 1 === preg_match('/\bSET\s+LOCAL\b/i', $clean);
    }
}
