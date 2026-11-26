<?php

declare(strict_types=1);

namespace Kongono\Database;

use Closure;
use Fiber;
use WeakMap;

/**
 * PostgreSQL Row-Level Security (RLS) policy generator and persistent-runtime-safe
 * transaction-local tenant scoping (`set_config(..., true)`).
 *
 * Acts as a database-enforced second lock directly behind `QueryBuilder::forTenant()` —
 * even raw SQL queries cannot read or write another tenant's rows, and unscoped sessions
 * fail closed (`NULLIF(current_setting(..., true), '')` evaluates to NULL => zero rows).
 */
final class RowLevelSecurity
{
    public const string DEFAULT_SETTING = 'app.current_tenant';

    /** @var ?WeakMap<ConnectionInterface, array{depth: int, fiber: ?Fiber<mixed, mixed, mixed, mixed>}> */
    private static ?WeakMap $activeState = null;

    /**
     * Compile PostgreSQL DDL statements enabling & forcing fail-closed tenant RLS on a table.
     *
     * @return list<string>
     */
    public static function enableSql(
        string $table,
        string $tenantColumn = 'workspace_id',
        string $settingName = self::DEFAULT_SETTING,
        ?string $policyName = null,
    ): array {
        $dialect = Dialect::Postgres;
        $quotedTable = $dialect->quoteIdentifier($table);
        $quotedColumn = $dialect->quoteIdentifier($tenantColumn);
        $cleanSetting = self::validateSettingName($settingName);
        $policy = $dialect->quoteIdentifier($policyName ?? sprintf('kongono_rls_%s_%s', str_replace('.', '_', $table), str_replace('.', '_', $tenantColumn)));

        $expr = sprintf(
            "%s::text = NULLIF(current_setting('%s', true), '')",
            $quotedColumn,
            $cleanSetting,
        );

        return [
            sprintf('ALTER TABLE %s ENABLE ROW LEVEL SECURITY', $quotedTable),
            sprintf('ALTER TABLE %s FORCE ROW LEVEL SECURITY', $quotedTable),
            sprintf('DROP POLICY IF EXISTS %s ON %s', $policy, $quotedTable),
            sprintf('CREATE POLICY %s ON %s USING (%s) WITH CHECK (%s)', $policy, $quotedTable, $expr, $expr),
        ];
    }

    /**
     * Compile PostgreSQL DDL statements removing the tenant RLS policy (for migration rollbacks).
     *
     * @return list<string>
     */
    public static function disableSql(
        string $table,
        string $tenantColumn = 'workspace_id',
        ?string $policyName = null,
    ): array {
        $dialect = Dialect::Postgres;
        $quotedTable = $dialect->quoteIdentifier($table);
        $policy = $dialect->quoteIdentifier($policyName ?? sprintf('kongono_rls_%s_%s', str_replace('.', '_', $table), str_replace('.', '_', $tenantColumn)));

        return [
            sprintf('DROP POLICY IF EXISTS %s ON %s', $policy, $quotedTable),
            sprintf('ALTER TABLE %s NO FORCE ROW LEVEL SECURITY', $quotedTable),
            sprintf('ALTER TABLE %s DISABLE ROW LEVEL SECURITY', $quotedTable),
        ];
    }

    /** Execute fail-closed PostgreSQL RLS DDL directly against a Postgres connection. */
    public static function enable(
        ConnectionInterface $db,
        string $table,
        string $tenantColumn = 'workspace_id',
        string $settingName = self::DEFAULT_SETTING,
        ?string $policyName = null,
    ): void {
        if (Dialect::Postgres !== $db->dialect()) {
            throw new DatabaseException(sprintf('RowLevelSecurity::enable() requires PostgreSQL (got %s).', $db->dialect()->value));
        }

        foreach (self::enableSql($table, $tenantColumn, $settingName, $policyName) as $sql) {
            $db->execute($sql);
        }
    }

    /**
     * Execute `$callback` inside a PostgreSQL transaction with transaction-local tenant GUC
     * (`SELECT set_config($settingName, $tenantId, true)`).
     *
     * Because `is_local = true`, PostgreSQL automatically clears the tenant binding when the
     * transaction commits or rolls back — preventing cross-request leakage in FrankenPHP,
     * RoadRunner, and PgBouncer pooled connections.
     *
     * @template T
     * @param Closure(ConnectionInterface): T $callback
     * @return T
     */
    public static function withTenant(
        ConnectionInterface $db,
        int|string|TenantContext $tenant,
        Closure $callback,
        string $settingName = self::DEFAULT_SETTING,
    ): mixed {
        if (Dialect::Postgres !== $db->dialect()) {
            throw new DatabaseException(sprintf('RowLevelSecurity::withTenant() requires PostgreSQL (got %s).', $db->dialect()->value));
        }

        $cleanSetting = self::validateSettingName($settingName);
        $rawId = $tenant instanceof TenantContext ? $tenant->tenantId : $tenant;
        $tenantId = trim((string) $rawId);
        if ('' === $tenantId || strlen($tenantId) > 128 || false !== strpbrk($tenantId, "\0\r\n")) {
            throw new DatabaseException('RowLevelSecurity tenant ID must be a non-empty string (<= 128 bytes) without control bytes.');
        }

        if (null === self::$activeState) {
            /** @var WeakMap<ConnectionInterface, array{depth: int, fiber: ?Fiber<mixed, mixed, mixed, mixed>}> $map */
            $map = new WeakMap();
            self::$activeState = $map;
        }
        $currentFiber = Fiber::getCurrent();
        $prevState = self::$activeState[$db] ?? null;
        if (null !== $prevState && $prevState['fiber'] !== $currentFiber) {
            throw new DatabaseException('Concurrent Fiber access to RowLevelSecurity::withTenant() on a shared database connection is forbidden; use a dedicated connection per Fiber.');
        }

        $depth = null !== $prevState ? $prevState['depth'] : 0;
        $alreadyInTransaction = $depth > 0
            || ($db instanceof PdoConnection && $db->inTransaction())
            || ($db instanceof ReadWriteConnection && $db->inTransaction());

        /** @var array{depth: int, fiber: ?Fiber<mixed, mixed, mixed, mixed>} $nextOuter */
        $nextOuter = ['depth' => $depth + 1, 'fiber' => $currentFiber];
        self::$activeState[$db] = $nextOuter;

        try {
            return $db->transaction(static function (ConnectionInterface $tx) use ($db, $cleanSetting, $tenantId, $callback, $alreadyInTransaction, $currentFiber): mixed {
                $trackInnerTx = $tx !== $db && null !== self::$activeState;
                $innerPrevState = $trackInnerTx ? (self::$activeState[$tx] ?? null) : null;
                if (null !== $innerPrevState && $innerPrevState['fiber'] !== $currentFiber) {
                    throw new DatabaseException('Concurrent Fiber access to RowLevelSecurity::withTenant() on a shared transaction connection is forbidden.');
                }
                $innerPrevDepth = null !== $innerPrevState ? $innerPrevState['depth'] : 0;
                $nested = $alreadyInTransaction || ($trackInnerTx && $innerPrevDepth > 0);
                if ($trackInnerTx && null !== self::$activeState) {
                    /** @var array{depth: int, fiber: ?Fiber<mixed, mixed, mixed, mixed>} $nextInner */
                    $nextInner = ['depth' => $innerPrevDepth + 1, 'fiber' => $currentFiber];
                    self::$activeState[$tx] = $nextInner;
                }

                try {
                    $prior = '';
                    if ($nested) {
                        $priorRow = $tx->first('SELECT current_setting(?, true) AS prior_tenant', [$cleanSetting]);
                        $prior = is_string($priorRow['prior_tenant'] ?? null) ? $priorRow['prior_tenant'] : '';
                    }

                    \Kongono\Core\Capabilities\ExecutionCapabilities::withAllowedRlsSetConfig(
                        static fn(): ?array => $tx->first('SELECT set_config(?, ?, true)', [$cleanSetting, $tenantId]),
                    );
                    $result = $callback($tx);

                    // In a nested transaction / savepoint, PostgreSQL merges `set_config(..., true)` into the
                    // enclosing transaction on `RELEASE SAVEPOINT`; explicitly restore the outer tenant GUC on return.
                    if ($nested) {
                        \Kongono\Core\Capabilities\ExecutionCapabilities::withAllowedRlsSetConfig(
                            static fn(): ?array => $tx->first('SELECT set_config(?, ?, true)', [$cleanSetting, $prior]),
                        );
                    }

                    return $result;
                } finally {
                    if ($trackInnerTx && null !== self::$activeState) {
                        if (null === $innerPrevState) {
                            unset(self::$activeState[$tx]);
                        } else {
                            self::$activeState[$tx] = $innerPrevState;
                        }
                    }
                }
            });
        } finally {
            if (null === $prevState) {
                unset(self::$activeState[$db]);
            } else {
                self::$activeState[$db] = $prevState;
            }
        }
    }

    private static function validateSettingName(string $settingName): string
    {
        if (1 !== preg_match('/\A[a-z][a-z0-9_]{0,30}\.[a-z][a-z0-9_]{0,30}\z/', $settingName)) {
            throw new DatabaseException(sprintf(
                'Unsafe PostgreSQL RLS setting name "%s": expected lowercase "<namespace>.<key>" identifier.',
                $settingName,
            ));
        }

        return $settingName;
    }
}
