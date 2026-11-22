<?php

declare(strict_types=1);

namespace Kongono\Database;

use Closure;

/**
 * Request-scoped DataLoader / BatchLoader to eliminate N+1 SQL queries cleanly
 * without ActiveRecord lazy-loading magic.
 *
 * @template TKey of array-key
 * @template TVal
 */
final class BatchLoader
{
    /** @var array<TKey, TVal> */
    private array $cache = [];

    /**
     * Pre-populate a known key/value pair into the request-scoped cache.
     *
     * @param TKey $key
     * @param TVal $value
     * @return self<TKey, TVal>
     */
    public function prime(int|string $key, mixed $value): self
    {
        $this->cache[$key] = $value;

        return $this;
    }

    /**
     * Load many keys in a single deduplicated batch query, returning a map keyed by input key.
     *
     * @param list<TKey>                             $keys
     * @param Closure(list<TKey>): array<TKey, TVal> $batchFetcher
     * @return array<TKey, ?TVal>
     */
    public function loadMany(array $keys, Closure $batchFetcher): array
    {
        /** @var list<TKey> $missing */
        $missing = [];
        /** @var array<TKey, true> $seenMissing */
        $seenMissing = [];

        foreach ($keys as $key) {
            if (!array_key_exists($key, $this->cache) && !isset($seenMissing[$key])) {
                $missing[] = $key;
                $seenMissing[$key] = true;
            }
        }

        if ([] !== $missing) {
            $fetched = $batchFetcher($missing);
            foreach ($fetched as $k => $v) {
                $this->cache[$k] = $v;
            }
        }

        /** @var array<TKey, ?TVal> $results */
        $results = [];
        foreach ($keys as $key) {
            $results[$key] = $this->cache[$key] ?? null;
        }

        return $results;
    }

    public function clear(): void
    {
        $this->cache = [];
    }
}
