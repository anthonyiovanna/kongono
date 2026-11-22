<?php

declare(strict_types=1);

namespace Kongono\Database;

/**
 * Immutable per-operation tenant scope for fail-closed row-level multi-tenancy.
 *
 * Pair with `QueryBuilder::forTenant($tenant)` and `QueryBuilder::requireTenant()`
 * to make accidental cross-tenant reads, updates, or deletes impossible.
 */
final readonly class TenantContext
{
    public function __construct(
        public int|string $tenantId,
        public string $column = 'workspace_id',
    ) {
        if ('' === trim((string) $tenantId) || '' === trim($column)) {
            throw new DatabaseException('TenantContext requires a non-empty tenantId and column name.');
        }
    }
}
