<?php

declare(strict_types=1);

namespace Kongono\Database;

/**
 * Per-operation-scope N+1 SQL query detector.
 *
 * Plugs directly into `PdoConnection::onQuery($guard->record(...))` and resets cleanly
 * between requests so persistent runtimes never leak query counters across scopes.
 */
final class QueryGuard
{
    /** @var array<string, int> */
    private array $patternCounts = [];

    /** @var list<array{pattern: string, count: int}> */
    private array $violations = [];

    /** @param positive-int $threshold Trigger warning/exception when the same normalized SQL runs >= threshold times */
    public function __construct(
        private readonly int $threshold = 5,
        private readonly bool $throwOnViolation = false,
    ) {}

    public function record(string $sql, float $_durationMs = 0.0): void
    {
        $pattern = self::normalizeSql($sql);
        $count = ($this->patternCounts[$pattern] ?? 0) + 1;
        $this->patternCounts[$pattern] = $count;

        if ($count === $this->threshold) {
            $this->violations[] = ['pattern' => $pattern, 'count' => $count];
        } elseif ($count > $this->threshold) {
            foreach ($this->violations as $idx => $v) {
                if ($v['pattern'] === $pattern) {
                    $this->violations[$idx]['count'] = $count;
                    break;
                }
            }
        }

        if ($this->throwOnViolation && $count >= $this->threshold) {
            throw new DatabaseException(sprintf(
                'Potential N+1 query detected (%d executions of "%s"). Use BatchLoader or a JOIN query.',
                $count,
                $pattern,
            ));
        }
    }

    /** @return list<array{pattern: string, count: int}> */
    public function violations(): array
    {
        return $this->violations;
    }

    public function hasViolations(): bool
    {
        return [] !== $this->violations;
    }

    /** @return array<string, int> */
    public function counts(): array
    {
        return $this->patternCounts;
    }

    public function reset(): void
    {
        $this->patternCounts = [];
        $this->violations = [];
    }

    /** Normalize SQL literals & IN-lists into a canonical query shape for N+1 grouping. */
    public static function normalizeSql(string $sql): string
    {
        $normalized = trim((string) preg_replace('/\s+/', ' ', $sql));
        $normalized = (string) preg_replace("/'(?:''|[^'])*'/", '?', $normalized);
        $normalized = (string) preg_replace('/\b\d+(?:\.\d+)?\b/', '?', $normalized);
        $normalized = (string) preg_replace('/\bIN\s*\(\s*\?(?:\s*,\s*\?)*\s*\)/i', 'IN (?)', $normalized);

        return $normalized;
    }
}
