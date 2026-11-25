<?php

declare(strict_types=1);

namespace Kongono\Database;

use Closure;

/**
 * Parameterized, full-featured SQL QueryBuilder supporting JOINs, WHERE/OR/IN/NULL,
 * nested WHERE groups, UPSERT, GROUP BY, HAVING, ORDER BY, LIMIT/OFFSET, and RETURNING.
 */
final class QueryBuilder
{
    private const array OPERATORS = ['=', '!=', '<>', '<', '<=', '>', '>=', 'LIKE', 'ILIKE'];

    /** @var list<string> */
    private array $columns = ['*'];

    private bool $distinct = false;

    /** @var list<array{type: string, table: string, left: string, operator: string, right: string}> */
    private array $joins = [];

    /** @var list<array{boolean: 'AND'|'OR', sql: string, bindings: list<mixed>}> */
    private array $wheres = [];

    /** @var list<array{sql: string, bindings: list<mixed>}> */
    private array $invariantConstraints = [];

    /** @var list<string> */
    private array $groups = [];

    /** @var list<array{sql: string, bindings: list<mixed>}> */
    private array $havings = [];

    /** @var list<array{column: string, direction: 'ASC'|'DESC'}> */
    private array $orders = [];

    private ?int $limit = null;
    private ?int $offset = null;
    private ?string $requiredTenantColumn = null;
    private bool $tenantScoped = false;

    /** @var array{column: string, value: int|string}|null */
    private ?array $tenantConstraint = null;

    public function __construct(
        private readonly string $table,
        private readonly Dialect $dialect = Dialect::Postgres,
        private readonly ?ConnectionInterface $connection = null,
        private readonly ?string $alias = null,
    ) {}

    public function select(string ...$columns): self
    {
        $this->columns = [] !== $columns ? array_values($columns) : ['*'];

        return $this;
    }

    public function distinct(bool $enabled = true): self
    {
        $this->distinct = $enabled;

        return $this;
    }

    public function join(string $table, string $left, string $operator, string $right, string $type = 'INNER'): self
    {
        $op = strtoupper(trim($operator));
        if (!in_array($op, self::OPERATORS, true)) {
            throw new DatabaseException(sprintf('Unsupported JOIN operator "%s".', $operator));
        }
        $joinType = strtoupper(trim($type));
        if (!in_array($joinType, ['INNER', 'LEFT', 'RIGHT', 'FULL'], true)) {
            throw new DatabaseException(sprintf('Unsupported JOIN type "%s".', $type));
        }
        $this->joins[] = [
            'type' => $joinType,
            'table' => $table,
            'left' => $left,
            'operator' => $op,
            'right' => $right,
        ];

        return $this;
    }

    public function leftJoin(string $table, string $left, string $operator, string $right): self
    {
        return $this->join($table, $left, $operator, $right, 'LEFT');
    }

    public function where(string $column, string $operator, mixed $value): self
    {
        if (null !== $this->requiredTenantColumn && '=' === trim($operator) && $this->matchesTenantColumn($column, $this->requiredTenantColumn)) {
            $this->tenantScoped = true;
        }

        return $this->addWhere('AND', $column, $operator, $value);
    }

    /**
     * Bind a fail-closed row-level tenant scope (`WHERE <column> = ?`).
     * Stored independently from user WHERE clauses so top-level `orWhere()` can NEVER
     * escape tenant isolation via SQL AND/OR operator precedence.
     */
    public function forTenant(TenantContext|int|string $tenant, string $column = 'workspace_id'): self
    {
        $col = $tenant instanceof TenantContext ? $tenant->column : $column;
        $id = $tenant instanceof TenantContext ? $tenant->tenantId : $tenant;

        if ('' === trim($col)) {
            throw new DatabaseException('Tenant column name cannot be empty.');
        }
        if ('' === trim((string) $id)) {
            throw new DatabaseException('Tenant ID cannot be empty.');
        }

        $baseNew = strtolower(self::baseTenantColumn($col));
        if (null !== $this->tenantConstraint) {
            if (strtolower(self::baseTenantColumn($this->tenantConstraint['column'])) !== $baseNew) {
                throw new DatabaseException(sprintf(
                    'Conflicting forTenant() columns on "%s": already scoped to "%s", cannot re-scope to "%s".',
                    $this->table,
                    $this->tenantConstraint['column'],
                    $col,
                ));
            }
            if ($this->tenantConstraint['value'] !== $id) {
                throw new DatabaseException(sprintf(
                    'Conflicting forTenant() IDs on "%s": query is already locked to a different tenant on "%s".',
                    $this->table,
                    $this->tenantConstraint['column'],
                ));
            }
        }
        if (null !== $this->requiredTenantColumn && strtolower(self::baseTenantColumn($this->requiredTenantColumn)) !== $baseNew) {
            throw new DatabaseException(sprintf(
                'Conflicting forTenant("%s") on "%s": query requires tenant column "%s".',
                $col,
                $this->table,
                $this->requiredTenantColumn,
            ));
        }

        $this->requiredTenantColumn = $col;
        $this->tenantScoped = true;
        $this->tenantConstraint = ['column' => $col, 'value' => $id];

        return $this;
    }

    /**
     * Enforce that this query MUST include an equality filter on `$column` (or `forTenant()`)
     * before compiling SELECT, UPDATE, or DELETE SQL — preventing cross-tenant data leaks.
     */
    public function requireTenant(string $column = 'workspace_id'): self
    {
        if ('' === trim($column)) {
            throw new DatabaseException('Required tenant column name cannot be empty.');
        }
        if (null !== $this->requiredTenantColumn && strtolower(self::baseTenantColumn($this->requiredTenantColumn)) !== strtolower(self::baseTenantColumn($column))) {
            $this->tenantScoped = false;
            if (null !== $this->tenantConstraint) {
                $this->invariantConstraints[] = [
                    'sql' => sprintf('%s = ?', $this->dialect->quoteIdentifier($this->tenantConstraint['column'])),
                    'bindings' => [$this->tenantConstraint['value']],
                ];
                $this->tenantConstraint = null;
            }
        }
        $this->requiredTenantColumn = $column;

        return $this;
    }

    public function orWhere(string $column, string $operator, mixed $value): self
    {
        return $this->addWhere('OR', $column, $operator, $value);
    }

    /** @param list<mixed> $values */
    public function whereIn(string $column, array $values): self
    {
        if ([] === $values) {
            $this->wheres[] = ['boolean' => 'AND', 'sql' => '1 = 0', 'bindings' => []];

            return $this;
        }
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $this->wheres[] = [
            'boolean' => 'AND',
            'sql' => sprintf('%s IN (%s)', $this->dialect->quoteIdentifier($column), $placeholders),
            'bindings' => $values,
        ];

        return $this;
    }

    public function whereNull(string $column): self
    {
        $this->wheres[] = [
            'boolean' => 'AND',
            'sql' => sprintf('%s IS NULL', $this->dialect->quoteIdentifier($column)),
            'bindings' => [],
        ];

        return $this;
    }

    public function whereNotNull(string $column): self
    {
        $this->wheres[] = [
            'boolean' => 'AND',
            'sql' => sprintf('%s IS NOT NULL', $this->dialect->quoteIdentifier($column)),
            'bindings' => [],
        ];

        return $this;
    }

    /** Exclude soft-deleted rows (`WHERE deleted_at IS NULL`), immune to top-level `orWhere()` precedence bypasses. */
    public function withoutTrashed(string $column = 'deleted_at'): self
    {
        $this->invariantConstraints[] = [
            'sql' => sprintf('%s IS NULL', $this->dialect->quoteIdentifier($column)),
            'bindings' => [],
        ];

        return $this;
    }

    /** Include ONLY soft-deleted rows (`WHERE deleted_at IS NOT NULL`), immune to top-level `orWhere()` precedence bypasses. */
    public function onlyTrashed(string $column = 'deleted_at'): self
    {
        $this->invariantConstraints[] = [
            'sql' => sprintf('%s IS NOT NULL', $this->dialect->quoteIdentifier($column)),
            'bindings' => [],
        ];

        return $this;
    }

    /** Soft-delete matching rows by setting `$column` to an ISO-8601 UTC timestamp (enforces active tenant scopes). */
    public function softDelete(string $column = 'deleted_at', ?string $timestamp = null): int
    {
        return $this->update([$column => $timestamp ?? gmdate(DATE_ATOM)]);
    }

    /** Restore soft-deleted matching rows by setting `$column` back to `NULL` (enforces active tenant scopes). */
    public function restore(string $column = 'deleted_at'): int
    {
        return $this->update([$column => null]);
    }

    public function whereLike(string $column, string $pattern): self
    {
        return $this->where($column, Dialect::Postgres === $this->dialect ? 'ILIKE' : 'LIKE', $pattern);
    }

    public function whereBetween(string $column, mixed $min, mixed $max): self
    {
        $this->wheres[] = [
            'boolean' => 'AND',
            'sql' => sprintf('%s BETWEEN ? AND ?', $this->dialect->quoteIdentifier($column)),
            'bindings' => [$min, $max],
        ];

        return $this;
    }

    /**
     * Add a parenthesized nested WHERE group: `AND (...)`.
     *
     * @param Closure(self): void $callback
     * @param 'AND'|'OR'          $boolean
     */
    public function whereGroup(Closure $callback, string $boolean = 'AND'): self
    {
        $bool = strtoupper(trim($boolean));
        if ('AND' !== $bool && 'OR' !== $bool) {
            throw new DatabaseException(sprintf('Unsupported WHERE boolean "%s".', $boolean));
        }

        $nested = new self($this->table, $this->dialect, $this->connection, $this->alias);
        $callback($nested);
        if (null === $nested->tenantConstraint && [] === $nested->invariantConstraints && [] === $nested->wheres) {
            return $this;
        }

        $bindings = [];
        $compiledGroup = $nested->compileWhereClause($bindings);
        if (null === $compiledGroup || '' === $compiledGroup) {
            return $this;
        }

        $this->wheres[] = [
            'boolean' => $bool,
            'sql' => '(' . $compiledGroup . ')',
            'bindings' => $bindings,
        ];

        return $this;
    }

    /**
     * Add a parenthesized nested OR WHERE group: `OR (...)`.
     *
     * @param Closure(self): void $callback
     */
    public function orWhereGroup(Closure $callback): self
    {
        return $this->whereGroup($callback, 'OR');
    }

    public function groupBy(string ...$columns): self
    {
        array_push($this->groups, ...$columns);

        return $this;
    }

    public function having(string $column, string $operator, mixed $value): self
    {
        $op = strtoupper(trim($operator));
        if (!in_array($op, self::OPERATORS, true)) {
            throw new DatabaseException(sprintf('Unsupported HAVING operator "%s".', $operator));
        }
        $this->havings[] = [
            'sql' => sprintf('%s %s ?', $this->quoteColumnOrExpression($column), $op),
            'bindings' => [$value],
        ];

        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $dir = 'DESC' === strtoupper($direction) ? 'DESC' : 'ASC';
        $this->orders[] = ['column' => $column, 'direction' => $dir];

        return $this;
    }

    public function limit(int $limit): self
    {
        if ($limit < 0) {
            throw new DatabaseException('LIMIT cannot be negative.');
        }
        $this->limit = $limit;

        return $this;
    }

    public function offset(int $offset): self
    {
        if ($offset < 0) {
            throw new DatabaseException('OFFSET cannot be negative.');
        }
        $this->offset = $offset;

        return $this;
    }

    /** Compile SELECT SQL + bindings. */
    public function toSelectSql(): Sql
    {
        $this->assertTenantScoped('SELECT');
        $bindings = [];
        $select = implode(', ', array_map($this->quoteColumnOrExpression(...), $this->columns));
        $from = $this->dialect->quoteIdentifier($this->table);
        if (null !== $this->alias) {
            $from .= ' AS ' . $this->dialect->quoteIdentifier($this->alias);
        }

        $selectKeyword = $this->distinct ? 'SELECT DISTINCT' : 'SELECT';
        $parts = [sprintf('%s %s FROM %s', $selectKeyword, $select, $from)];

        foreach ($this->joins as $join) {
            $parts[] = sprintf(
                '%s JOIN %s ON %s %s %s',
                $join['type'],
                $this->dialect->quoteIdentifier($join['table']),
                $this->dialect->quoteIdentifier($join['left']),
                $join['operator'],
                $this->dialect->quoteIdentifier($join['right']),
            );
        }

        $whereSql = $this->compileWhereClause($bindings);
        if (null !== $whereSql) {
            $parts[] = 'WHERE ' . $whereSql;
        }

        if ([] !== $this->groups) {
            $parts[] = 'GROUP BY ' . implode(', ', array_map($this->dialect->quoteIdentifier(...), $this->groups));
        }

        if ([] !== $this->havings) {
            $havingClauses = [];
            foreach ($this->havings as $having) {
                $havingClauses[] = $having['sql'];
                array_push($bindings, ...$having['bindings']);
            }
            $parts[] = 'HAVING ' . implode(' AND ', $havingClauses);
        }

        if ([] !== $this->orders) {
            $orderParts = array_map(
                fn(array $o): string => sprintf('%s %s', $this->dialect->quoteIdentifier($o['column']), $o['direction']),
                $this->orders,
            );
            $parts[] = 'ORDER BY ' . implode(', ', $orderParts);
        }

        if (null !== $this->limit) {
            $parts[] = sprintf('LIMIT %d', $this->limit);
        }
        if (null !== $this->offset) {
            $parts[] = sprintf('OFFSET %d', $this->offset);
        }

        return new Sql(implode(' ', $parts), $bindings);
    }

    /**
     * Compile a batch multi-row parameterized INSERT statement.
     *
     * @param list<array<string, mixed>> $rows
     */
    public function toInsertManySql(array $rows): Sql
    {
        if ([] === $rows) {
            throw new DatabaseException('Cannot compile batch INSERT with empty rows.');
        }

        $scopedRows = [];
        foreach ($rows as $row) {
            $scopedRows[] = $this->applyTenantToWriteRow($row, 'INSERT');
        }

        $columns = array_keys($scopedRows[0]);
        if ([] === $columns) {
            throw new DatabaseException('Cannot compile batch INSERT with empty columns.');
        }

        $quotedColumns = array_map($this->dialect->quoteIdentifier(...), $columns);
        $rowPlaceholder = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
        $groups = [];
        $bindings = [];

        $tenantCol = null !== $this->tenantConstraint
            ? self::baseTenantColumn($this->tenantConstraint['column'])
            : (null !== $this->requiredTenantColumn ? self::baseTenantColumn($this->requiredTenantColumn) : null);

        foreach ($scopedRows as $row) {
            $groups[] = $rowPlaceholder;
            foreach ($columns as $col) {
                $val = $row[$col] ?? null;
                if (null !== $tenantCol && strtolower($col) === strtolower($tenantCol) && (null === $val || '' === trim((string) (is_scalar($val) ? $val : '')))) {
                    throw new DatabaseException(sprintf(
                        'Refusing cross-tenant INSERT on "%s": row is missing required tenant column "%s".',
                        $this->table,
                        $tenantCol,
                    ));
                }
                $bindings[] = $val;
            }
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES %s',
            $this->dialect->quoteIdentifier($this->table),
            implode(', ', $quotedColumns),
            implode(', ', $groups),
        );

        return new Sql($sql, $bindings);
    }

    /**
     * Compile a multi-row UPSERT (PostgreSQL/SQLite ON CONFLICT or MySQL ON DUPLICATE KEY UPDATE).
     *
     * @param list<array<string, mixed>> $rows
     * @param list<string>               $conflictColumns
     * @param list<string>               $updateColumns   Empty = DO NOTHING / INSERT IGNORE
     */
    public function toUpsertSql(array $rows, array $conflictColumns, array $updateColumns = []): Sql
    {
        if ([] === $conflictColumns) {
            throw new DatabaseException('Cannot compile UPSERT with empty conflict columns.');
        }

        $base = $this->toInsertManySql($rows);

        if (null !== $this->requiredTenantColumn && null === $this->tenantConstraint && [] !== $updateColumns) {
            throw new DatabaseException(sprintf(
                'Refusing UPSERT with DO UPDATE on "%s" under requireTenant("%s"): bind an explicit forTenant() scope to guard conflict updates.',
                $this->table,
                $this->requiredTenantColumn,
            ));
        }

        $tenantBase = null !== $this->tenantConstraint ? self::baseTenantColumn($this->tenantConstraint['column']) : null;
        if (null !== $tenantBase && [] !== $updateColumns) {
            $lowerTenant = strtolower($tenantBase);
            foreach ($updateColumns as $uCol) {
                if (strtolower(self::baseTenantColumn($uCol)) === $lowerTenant) {
                    throw new DatabaseException(sprintf('Refusing UPSERT on "%s": cannot overwrite tenant column "%s".', $this->table, $tenantBase));
                }
            }
            if (Dialect::Mysql === $this->dialect) {
                $hasTenantConflict = false;
                foreach ($conflictColumns as $cCol) {
                    if (strtolower(self::baseTenantColumn($cCol)) === $lowerTenant) {
                        $hasTenantConflict = true;
                        break;
                    }
                }
                if (!$hasTenantConflict) {
                    throw new DatabaseException(sprintf('Refusing MySQL UPSERT on "%s": tenant column "%s" must be included in conflictColumns to prevent cross-tenant overwrite.', $this->table, $tenantBase));
                }
            }
        }

        if (Dialect::Mysql === $this->dialect) {
            if ([] === $updateColumns) {
                $ignoreSql = (string) preg_replace('/\AINSERT INTO /', 'INSERT IGNORE INTO ', $base->query, 1);

                return new Sql($ignoreSql, $base->bindings);
            }

            $bindings = $base->bindings;
            $updates = [];
            foreach ($updateColumns as $col) {
                $qCol = $this->dialect->quoteIdentifier($col);
                if (null !== $this->tenantConstraint && null !== $tenantBase) {
                    // MySQL ON DUPLICATE KEY UPDATE fires on ANY unique/PK collision; guard every column update by tenant ownership
                    $qTenant = $this->dialect->quoteIdentifier($tenantBase);
                    $updates[] = sprintf('%s = IF(%s = ?, VALUES(%s), %s)', $qCol, $qTenant, $qCol, $qCol);
                    $bindings[] = $this->tenantConstraint['value'];
                } else {
                    $updates[] = sprintf('%s = VALUES(%s)', $qCol, $qCol);
                }
            }

            return new Sql($base->query . ' ON DUPLICATE KEY UPDATE ' . implode(', ', $updates), $bindings);
        }

        $conflict = implode(', ', array_map($this->dialect->quoteIdentifier(...), $conflictColumns));
        if ([] === $updateColumns) {
            return new Sql(sprintf('%s ON CONFLICT (%s) DO NOTHING', $base->query, $conflict), $base->bindings);
        }

        $updates = array_map(
            fn(string $col): string => sprintf('%s = EXCLUDED.%s', $this->dialect->quoteIdentifier($col), $this->dialect->quoteIdentifier($col)),
            $updateColumns,
        );

        $sql = sprintf('%s ON CONFLICT (%s) DO UPDATE SET %s', $base->query, $conflict, implode(', ', $updates));
        $bindings = $base->bindings;
        if (null !== $this->tenantConstraint && null !== $tenantBase) {
            $sql .= sprintf(
                ' WHERE %s.%s = ?',
                $this->dialect->quoteIdentifier($this->table),
                $this->dialect->quoteIdentifier($tenantBase),
            );
            $bindings[] = $this->tenantConstraint['value'];
        }

        return new Sql($sql, $bindings);
    }

    /**
     * Compile INSERT SQL + bindings.
     *
     * @param array<string, mixed> $values
     * @param list<string>         $returning
     */
    public function toInsertSql(array $values, array $returning = []): Sql
    {
        if ([] === $values) {
            throw new DatabaseException('Cannot compile INSERT with empty values.');
        }

        $scopedValues = $this->applyTenantToWriteRow($values, 'INSERT');

        $columns = array_map($this->dialect->quoteIdentifier(...), array_keys($scopedValues));
        $placeholders = implode(', ', array_fill(0, count($scopedValues), '?'));
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->dialect->quoteIdentifier($this->table),
            implode(', ', $columns),
            $placeholders,
        );

        if ([] !== $returning && $this->dialect->supportsReturning()) {
            $sql .= ' RETURNING ' . implode(', ', array_map($this->dialect->quoteIdentifier(...), $returning));
        }

        return new Sql($sql, array_values($scopedValues));
    }

    /**
     * Compile UPDATE SQL + bindings (requires at least one WHERE clause to prevent accidental full-table wipes).
     *
     * @param array<string, mixed> $values
     * @param list<string>         $returning
     */
    public function toUpdateSql(array $values, array $returning = [], bool $allowUnscoped = false): Sql
    {
        $this->assertTenantScoped('UPDATE');
        if ([] === $values) {
            throw new DatabaseException('Cannot compile UPDATE with empty values.');
        }
        if ([] === $this->wheres && null === $this->tenantConstraint && [] === $this->invariantConstraints && !$allowUnscoped) {
            throw new DatabaseException('Refusing unscoped UPDATE without WHERE clause. Pass $allowUnscoped = true to override.');
        }

        $activeTenantCol = $this->tenantConstraint['column'] ?? $this->requiredTenantColumn;
        $lowerTenantBase = null !== $activeTenantCol ? strtolower(self::baseTenantColumn($activeTenantCol)) : null;

        $sets = [];
        $bindings = [];
        foreach ($values as $column => $value) {
            if (null !== $lowerTenantBase && strtolower(self::baseTenantColumn($column)) === $lowerTenantBase) {
                throw new DatabaseException(sprintf(
                    'Refusing cross-tenant UPDATE on "%s": cannot modify tenant column "%s".',
                    $this->table,
                    $column,
                ));
            }
            $sets[] = sprintf('%s = ?', $this->dialect->quoteIdentifier($column));
            $bindings[] = $value;
        }

        $sql = sprintf('UPDATE %s SET %s', $this->dialect->quoteIdentifier($this->table), implode(', ', $sets));
        $whereSql = $this->compileWhereClause($bindings);
        if (null !== $whereSql) {
            $sql .= ' WHERE ' . $whereSql;
        }

        if ([] !== $returning && $this->dialect->supportsReturning()) {
            $sql .= ' RETURNING ' . implode(', ', array_map($this->dialect->quoteIdentifier(...), $returning));
        }

        return new Sql($sql, $bindings);
    }

    /**
     * Compile DELETE SQL + bindings (requires WHERE clause by default).
     *
     * @param list<string> $returning
     */
    public function toDeleteSql(array $returning = [], bool $allowUnscoped = false): Sql
    {
        $this->assertTenantScoped('DELETE');
        if ([] === $this->wheres && null === $this->tenantConstraint && [] === $this->invariantConstraints && !$allowUnscoped) {
            throw new DatabaseException('Refusing unscoped DELETE without WHERE clause. Pass $allowUnscoped = true to override.');
        }

        $bindings = [];
        $sql = sprintf('DELETE FROM %s', $this->dialect->quoteIdentifier($this->table));
        $whereSql = $this->compileWhereClause($bindings);
        if (null !== $whereSql) {
            $sql .= ' WHERE ' . $whereSql;
        }

        if ([] !== $returning && $this->dialect->supportsReturning()) {
            $sql .= ' RETURNING ' . implode(', ', array_map($this->dialect->quoteIdentifier(...), $returning));
        }

        return new Sql($sql, $bindings);
    }

    // --- Execution helpers (when bound to a ConnectionInterface) ---

    /** @return list<array<string, mixed>> */
    public function get(): array
    {
        $sql = $this->toSelectSql();

        return $this->requireConnection()->query($sql->query, $sql->bindings);
    }

    /** @return array<string, mixed>|null */
    public function first(): ?array
    {
        $sql = (clone $this)->limit(1)->toSelectSql();

        return $this->requireConnection()->first($sql->query, $sql->bindings);
    }

    /** Count matching rows using a clean COUNT(*) query (wrapping GROUP BY / DISTINCT as a subquery). */
    public function count(): int
    {
        $clone = clone $this;
        $clone->orders = [];
        $clone->limit = null;
        $clone->offset = null;

        if ([] !== $clone->groups || $clone->distinct) {
            $innerSql = $clone->toSelectSql();
            $row = $clone->requireConnection()->first(
                sprintf('SELECT COUNT(*) AS aggregate_count FROM (%s) AS kongono_count_sub', $innerSql->query),
                $innerSql->bindings,
            );
        } else {
            $clone->columns = ['COUNT(*) AS aggregate_count'];
            $countSql = $clone->toSelectSql();
            $row = $clone->requireConnection()->first($countSql->query, $countSql->bindings);
        }

        return is_array($row) && is_numeric($row['aggregate_count'] ?? null)
            ? (int) $row['aggregate_count']
            : 0;
    }

    /** Check whether at least one row matches the current query. */
    public function exists(): bool
    {
        return $this->count() > 0;
    }

    /**
     * Extract a single column's values across all matching rows.
     *
     * @return list<mixed>
     */
    public function pluck(string $column): array
    {
        $clone = clone $this;
        $rows = $clone->select($column)->get();
        $trimmed = trim($column);
        if (1 === preg_match('/\s+AS\s+([a-zA-Z_][a-zA-Z0-9_]*)\z/i', $trimmed, $m)) {
            $resultKey = $m[1];
        } else {
            $dot = strrpos($trimmed, '.');
            $resultKey = false === $dot ? $trimmed : substr($trimmed, $dot + 1);
        }

        $values = [];
        foreach ($rows as $row) {
            if (array_key_exists($resultKey, $row)) {
                $values[] = $row[$resultKey];
            } elseif (array_key_exists($column, $row)) {
                $values[] = $row[$column];
            }
        }

        return $values;
    }

    /**
     * Execute a batch multi-row parameterized INSERT.
     *
     * @param list<array<string, mixed>> $rows
     */
    public function insertMany(array $rows): int
    {
        $sql = $this->toInsertManySql($rows);

        return $this->requireConnection()->execute($sql->query, $sql->bindings);
    }

    /**
     * Paginate matching rows into an immutable Page value object.
     *
     * @return Page<array<string, mixed>>
     */
    public function paginate(int $page = 1, int $perPage = 20): Page
    {
        $safePage = max(1, $page);
        $safePerPage = max(1, $perPage);
        $total = $this->count();

        $clone = clone $this;
        $items = $clone->limit($safePerPage)->offset(($safePage - 1) * $safePerPage)->get();

        return new Page($items, $total, $safePage, $safePerPage);
    }

    /**
     * Fetch rows and hydrate each row into a typed PHP 8.5 readonly DTO (supports snake_case -> camelCase).
     *
     * @template T of object
     * @param class-string<T> $dtoClass
     * @return list<T>
     */
    public function getAs(string $dtoClass): array
    {
        return array_map(fn(array $row): object => $this->hydrateDto($dtoClass, $row), $this->get());
    }

    /**
     * Fetch the first row hydrated into a typed PHP 8.5 readonly DTO.
     *
     * @template T of object
     * @param class-string<T> $dtoClass
     * @return ?T
     */
    public function firstAs(string $dtoClass): ?object
    {
        $row = $this->first();

        return null === $row ? null : $this->hydrateDto($dtoClass, $row);
    }

    /** @param array<string, mixed> $values */
    public function insert(array $values): int
    {
        $sql = $this->toInsertSql($values);

        return $this->requireConnection()->execute($sql->query, $sql->bindings);
    }

    /**
     * Execute a multi-row UPSERT against the bound database connection.
     *
     * @param non-empty-list<array<string, mixed>> $rows
     * @param non-empty-list<string>               $conflictColumns
     * @param list<string>                         $updateColumns
     */
    public function upsert(array $rows, array $conflictColumns, array $updateColumns = []): int
    {
        $sql = $this->toUpsertSql($rows, $conflictColumns, $updateColumns);

        return $this->requireConnection()->execute($sql->query, $sql->bindings);
    }

    /**
     * @param array<string, mixed> $values
     * @param list<string>         $returning
     * @return array<string, mixed>|null
     */
    public function insertReturning(array $values, array $returning = ['*']): ?array
    {
        $sql = $this->toInsertSql($values, $returning);

        return $this->requireConnection()->first($sql->query, $sql->bindings);
    }

    /** @param array<string, mixed> $values */
    public function update(array $values): int
    {
        $sql = $this->toUpdateSql($values);

        return $this->requireConnection()->execute($sql->query, $sql->bindings);
    }

    public function delete(): int
    {
        $sql = $this->toDeleteSql();

        return $this->requireConnection()->execute($sql->query, $sql->bindings);
    }

    private function assertTenantScoped(string $operation): void
    {
        if (null !== $this->requiredTenantColumn && !$this->tenantScoped) {
            throw new DatabaseException(sprintf(
                'Refusing cross-tenant %s on "%s": missing required tenant scope on column "%s". Call forTenant() or where("%s", "=", ...).',
                $operation,
                $this->table,
                $this->requiredTenantColumn,
                $this->requiredTenantColumn,
            ));
        }

        // Prevent SQL operator-precedence tenant bypass when requireTenant() is combined with top-level OR
        if (null !== $this->requiredTenantColumn && null === $this->tenantConstraint) {
            foreach ($this->wheres as $index => $where) {
                if ($index > 0 && 'OR' === $where['boolean']) {
                    throw new DatabaseException(sprintf(
                        'Refusing cross-tenant %s on "%s": top-level OR clause can bypass requireTenant("%s"). Use forTenant() or wrap OR conditions inside whereGroup().',
                        $operation,
                        $this->table,
                        $this->requiredTenantColumn,
                    ));
                }
            }
        }
    }

    /**
     * Compile combined tenant constraint + soft-delete invariants + user WHERE clauses safely.
     *
     * @param list<mixed> $bindings
     */
    private function compileWhereClause(array &$bindings): ?string
    {
        if (null === $this->tenantConstraint && [] === $this->invariantConstraints && [] === $this->wheres) {
            return null;
        }

        $userSql = '';
        $userBindings = [];
        $hasTopLevelOr = false;
        if ([] !== $this->wheres) {
            $clauses = [];
            foreach ($this->wheres as $i => $w) {
                if ($i > 0 && 'OR' === $w['boolean']) {
                    $hasTopLevelOr = true;
                }
                $clauses[] = (0 === $i ? '' : $w['boolean'] . ' ') . $w['sql'];
                array_push($userBindings, ...$w['bindings']);
            }
            $userSql = implode(' ', $clauses);
        }

        $invariantClauses = [];
        if (null !== $this->tenantConstraint) {
            $invariantClauses[] = sprintf('%s = ?', $this->dialect->quoteIdentifier($this->tenantConstraint['column']));
            $bindings[] = $this->tenantConstraint['value'];
        }
        foreach ($this->invariantConstraints as $inv) {
            $invariantClauses[] = $inv['sql'];
            array_push($bindings, ...$inv['bindings']);
        }

        if ([] !== $invariantClauses) {
            $invariantSql = implode(' AND ', $invariantClauses);
            if ('' === $userSql) {
                return $invariantSql;
            }

            array_push($bindings, ...$userBindings);

            return $hasTopLevelOr
                ? sprintf('%s AND (%s)', $invariantSql, $userSql)
                : sprintf('%s AND %s', $invariantSql, $userSql);
        }

        array_push($bindings, ...$userBindings);

        return $userSql;
    }

    private function matchesTenantColumn(string $column, string $required): bool
    {
        $clean = strtolower(trim($column));
        $target = strtolower(trim($required));

        if (str_contains($target, '.')) {
            return $clean === $target;
        }

        if ($clean === $target || $clean === strtolower($this->table) . '.' . $target) {
            return true;
        }

        return null !== $this->alias && $clean === strtolower($this->alias) . '.' . $target;
    }

    /**
     * Enforce tenant scope on INSERT / UPSERT rows.
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function applyTenantToWriteRow(array $row, string $operation): array
    {
        if (null !== $this->tenantConstraint) {
            $col = self::baseTenantColumn($this->tenantConstraint['column']);
            $lowerCol = strtolower($col);
            $expected = $this->tenantConstraint['value'];
            $matchedKey = null;
            foreach ($row as $k => $v) {
                if (strtolower(self::baseTenantColumn($k)) === $lowerCol) {
                    if (null !== $matchedKey) {
                        throw new DatabaseException(sprintf(
                            'Refusing ambiguous %s on "%s": duplicate case-variant keys for tenant column "%s".',
                            $operation,
                            $this->table,
                            $col,
                        ));
                    }
                    if ($v !== $expected) {
                        throw new DatabaseException(sprintf(
                            'Refusing cross-tenant %s on "%s": row value for "%s" conflicts with active forTenant() scope.',
                            $operation,
                            $this->table,
                            $col,
                        ));
                    }
                    $matchedKey = $k;
                }
            }
            if (null === $matchedKey) {
                $row[$col] = $expected;
            } elseif ($matchedKey !== $col) {
                unset($row[$matchedKey]);
                $row[$col] = $expected;
            }

            return $row;
        }

        if (null !== $this->requiredTenantColumn) {
            $col = self::baseTenantColumn($this->requiredTenantColumn);
            $lowerCol = strtolower($col);
            $foundKey = null;
            foreach ($row as $k => $v) {
                if (strtolower(self::baseTenantColumn($k)) === $lowerCol) {
                    if (null !== $foundKey) {
                        throw new DatabaseException(sprintf(
                            'Refusing ambiguous %s on "%s": duplicate case-variant keys for tenant column "%s".',
                            $operation,
                            $this->table,
                            $col,
                        ));
                    }
                    if (null === $v || '' === trim((string) (is_scalar($v) ? $v : ''))) {
                        throw new DatabaseException(sprintf(
                            'Refusing cross-tenant %s on "%s": tenant column "%s" cannot be empty.',
                            $operation,
                            $this->table,
                            $col,
                        ));
                    }
                    $foundKey = $k;
                }
            }
            if (null === $foundKey) {
                throw new DatabaseException(sprintf(
                    'Refusing cross-tenant %s on "%s": missing required tenant column "%s".',
                    $operation,
                    $this->table,
                    $col,
                ));
            }
            if ($foundKey !== $col) {
                $tenantVal = $row[$foundKey];
                unset($row[$foundKey]);
                $row[$col] = $tenantVal;
            }
        }

        return $row;
    }

    private static function baseTenantColumn(string $column): string
    {
        $trimmed = trim($column);
        $dot = strrpos($trimmed, '.');

        return false === $dot ? $trimmed : substr($trimmed, $dot + 1);
    }

    /** @param 'AND'|'OR' $boolean */
    private function addWhere(string $boolean, string $column, string $operator, mixed $value): self
    {
        $op = strtoupper(trim($operator));
        if (!in_array($op, self::OPERATORS, true)) {
            throw new DatabaseException(sprintf('Unsupported WHERE operator "%s".', $operator));
        }
        $this->wheres[] = [
            'boolean' => $boolean,
            'sql' => sprintf('%s %s ?', $this->dialect->quoteIdentifier($column), $op),
            'bindings' => [$value],
        ];

        return $this;
    }

    private function quoteColumnOrExpression(string $column): string
    {
        $trimmed = trim($column);
        // Allow simple aggregates like COUNT(*), SUM(amount), COUNT(id) AS total
        if (1 === preg_match('/\A(COUNT|SUM|AVG|MIN|MAX)\(\s*(\*|[a-zA-Z_][a-zA-Z0-9_.]*)\s*\)(?:\s+AS\s+([a-zA-Z_][a-zA-Z0-9_]*))?\z/i', $trimmed, $m)) {
            $fn = strtoupper($m[1]);
            $arg = '*' === $m[2] ? '*' : $this->dialect->quoteIdentifier($m[2]);
            $expr = sprintf('%s(%s)', $fn, $arg);
            if (isset($m[3])) {
                $expr .= ' AS ' . $this->dialect->quoteIdentifier($m[3]);
            }

            return $expr;
        }
        if (1 === preg_match('/\A([a-zA-Z_][a-zA-Z0-9_.]*)\s+AS\s+([a-zA-Z_][a-zA-Z0-9_]*)\z/i', $trimmed, $m)) {
            return $this->dialect->quoteIdentifier($m[1]) . ' AS ' . $this->dialect->quoteIdentifier($m[2]);
        }

        return $this->dialect->quoteIdentifier($trimmed);
    }

    private function requireConnection(): ConnectionInterface
    {
        if (null === $this->connection) {
            throw new DatabaseException('QueryBuilder is not bound to a ConnectionInterface. Call toSelectSql() or construct via $connection->table().');
        }

        return $this->connection;
    }

    /**
     * @template T of object
     * @param class-string<T>      $dtoClass
     * @param array<string, mixed> $row
     * @return T
     */
    private function hydrateDto(string $dtoClass, array $row): object
    {
        $ref = new \ReflectionClass($dtoClass);
        $ctor = $ref->getConstructor();
        if (null === $ctor) {
            return $ref->newInstance();
        }

        $args = [];
        foreach ($ctor->getParameters() as $param) {
            $name = $param->getName();
            $snake = strtolower((string) preg_replace('/[A-Z]/', '_$0', $name));
            if (array_key_exists($name, $row)) {
                $val = $row[$name];
            } elseif (array_key_exists($snake, $row)) {
                $val = $row[$snake];
            } elseif ($param->isDefaultValueAvailable()) {
                $val = $param->getDefaultValue();
            } elseif ($param->allowsNull()) {
                $val = null;
            } else {
                throw new DatabaseException(sprintf('Missing column "%s" (or "%s") when hydrating %s.', $name, $snake, $dtoClass));
            }
            $args[] = $val;
        }

        return $ref->newInstanceArgs($args);
    }
}
