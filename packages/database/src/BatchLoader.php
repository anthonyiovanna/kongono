<?php

declare(strict_types=1);

namespace Kongono\Database;

use Closure;

/**
 * Request-scoped DataLoader / BatchLoader & zero-N+1 typed eager relation helpers
 * (`hasMany`, `belongsTo`) without ActiveRecord lazy-loading magic.
 *
 * @template TKey of array-key
 * @template TVal
 */
final class BatchLoader
{
    /** @var array<TKey, TVal> */
    private array $cache = [];

    /** @var array<TKey, true> Tracks keys already queried (including negative cache misses) */
    private array $loaded = [];

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
        $this->loaded[$key] = true;

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
            if (!isset($this->loaded[$key]) && !isset($seenMissing[$key])) {
                $missing[] = $key;
                $seenMissing[$key] = true;
            }
        }

        if ([] !== $missing) {
            $fetched = $batchFetcher($missing);
            foreach ($missing as $k) {
                $this->loaded[$k] = true;
            }
            foreach ($fetched as $k => $v) {
                $this->cache[$k] = $v;
                $this->loaded[$k] = true;
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
        $this->loaded = [];
    }

    /**
     * Eager-load a 1-to-many relation in ONE single `WHERE IN (...)` query (zero N+1).
     * Works with both raw row arrays and typed PHP 8.5 readonly DTO objects.
     *
     * @template TChild = array<string, mixed>
     * @param list<array<string, mixed>|object>       $parents
     * @param ?Closure(array<string, mixed>): TChild  $mapper Optional row->DTO mapper
     * @return ($mapper is null ? array<int|string, list<array<string, mixed>>> : array<int|string, list<TChild>>)
     */
    public static function hasMany(
        ConnectionInterface $db,
        array $parents,
        string $childTable,
        string $foreignKey,
        string $localKey = 'id',
        ?Closure $mapper = null,
        ?string $softDeleteColumn = null,
        ?string $orderBy = null,
        string $direction = 'ASC',
        TenantContext|int|string|null $tenant = null,
        string $tenantColumn = 'workspace_id',
    ): array {
        $parentIds = self::extractKeys($parents, $localKey);
        $grouped = [];
        foreach ($parentIds as $pid) {
            $grouped[$pid] = [];
        }
        if ([] === $parentIds) {
            return $grouped;
        }

        $query = $db->table($childTable)->whereIn($foreignKey, $parentIds);
        if (null !== $tenant) {
            $query->forTenant($tenant, $tenantColumn);
        }
        if (null !== $softDeleteColumn && '' !== trim($softDeleteColumn)) {
            $query->withoutTrashed($softDeleteColumn);
        }
        if (null !== $orderBy && '' !== trim($orderBy)) {
            $query->orderBy($orderBy, $direction);
        }

        foreach ($query->get() as $row) {
            $fk = $row[$foreignKey] ?? null;
            if (!is_int($fk) && !is_string($fk)) {
                continue;
            }
            if (!array_key_exists($fk, $grouped)) {
                $grouped[$fk] = [];
            }
            $grouped[$fk][] = null !== $mapper ? $mapper($row) : $row;
        }

        return $grouped;
    }

    /**
     * Eager-load a many-to-1 (`belongsTo`) relation in ONE single `WHERE IN (...)` query (zero N+1).
     * Returns a lookup map keyed by parent `$ownerKey`.
     *
     * @template TParent = array<string, mixed>
     * @param list<array<string, mixed>|object>        $children
     * @param ?Closure(array<string, mixed>): TParent  $mapper Optional row->DTO mapper
     * @return ($mapper is null ? array<int|string, array<string, mixed>> : array<int|string, TParent>)
     */
    public static function belongsTo(
        ConnectionInterface $db,
        array $children,
        string $parentTable,
        string $foreignKey,
        string $ownerKey = 'id',
        ?Closure $mapper = null,
        ?string $softDeleteColumn = null,
        TenantContext|int|string|null $tenant = null,
        string $tenantColumn = 'workspace_id',
    ): array {
        $fkValues = self::extractKeys($children, $foreignKey);
        if ([] === $fkValues) {
            return [];
        }

        $query = $db->table($parentTable)->whereIn($ownerKey, $fkValues);
        if (null !== $tenant) {
            $query->forTenant($tenant, $tenantColumn);
        }
        if (null !== $softDeleteColumn && '' !== trim($softDeleteColumn)) {
            $query->withoutTrashed($softDeleteColumn);
        }

        $indexed = [];
        foreach ($query->get() as $row) {
            $pk = $row[$ownerKey] ?? null;
            if (!is_int($pk) && !is_string($pk)) {
                continue;
            }
            $indexed[$pk] = null !== $mapper ? $mapper($row) : $row;
        }

        return $indexed;
    }

    /**
     * @param list<array<string, mixed>|object> $records
     * @return list<int|string>
     */
    private static function extractKeys(array $records, string $key): array
    {
        $camel = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $key))));
        /** @var array<int|string, true> $seen */
        $seen = [];
        /** @var list<int|string> $keys */
        $keys = [];

        foreach ($records as $record) {
            $val = null;
            if (is_array($record)) {
                $val = $record[$key] ?? ($record[$camel] ?? null);
            } else {
                $vars = get_object_vars($record);
                if (array_key_exists($key, $vars)) {
                    $val = $vars[$key];
                } elseif (array_key_exists($camel, $vars)) {
                    $val = $vars[$camel];
                }
            }
            if ((is_int($val) || is_string($val)) && !isset($seen[$val])) {
                $seen[$val] = true;
                $keys[] = $val;
            }
        }

        return $keys;
    }
}
