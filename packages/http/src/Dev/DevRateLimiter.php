<?php

declare(strict_types=1);

namespace Kongono\Http\Dev;

/**
 * Bounded, instance-scoped sliding-window rate limiter for local Dev Studio AI companion endpoints.
 * Zero static state — safe across FrankenPHP / RoadRunner persistent workers.
 */
final class DevRateLimiter
{
    private const int MAX_BUCKETS = 64;

    /** @var array<string, array{window_start: int, count: int}> */
    private array $buckets = [];

    /**
     * Record a request for `$bucket` and return `0` if allowed, or retry-after seconds (> 0) if throttled.
     */
    public function attempt(string $bucket, int $maxRequests, int $windowSeconds = 60, ?int $now = null): int
    {
        if ($maxRequests <= 0) {
            return 0;
        }
        $ts = $now ?? time();
        $window = max(1, $windowSeconds);

        // Opportunistically prune expired buckets so memory stays strictly bounded
        if (count($this->buckets) >= self::MAX_BUCKETS) {
            foreach ($this->buckets as $k => $entry) {
                if (($ts - $entry['window_start']) >= $window) {
                    unset($this->buckets[$k]);
                }
            }
            if (count($this->buckets) >= self::MAX_BUCKETS) {
                $oldest = array_key_first($this->buckets);
                unset($this->buckets[$oldest]);
            }
        }

        $entry = $this->buckets[$bucket] ?? null;
        if (null === $entry || ($ts - $entry['window_start']) >= $window) {
            $this->buckets[$bucket] = ['window_start' => $ts, 'count' => 1];

            return 0;
        }

        if ($entry['count'] >= $maxRequests) {
            return max(1, ($entry['window_start'] + $window) - $ts);
        }

        ++$this->buckets[$bucket]['count'];

        return 0;
    }
}
