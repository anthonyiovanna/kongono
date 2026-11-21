<?php

declare(strict_types=1);

namespace Kongono\Database;

/**
 * Parameterized, full-featured SQL QueryBuilder supporting JOINs, WHERE/OR/IN/NULL,
 * GROUP BY, HAVING, ORDER BY, LIMIT/OFFSET, INSERT/UPDATE/DELETE, and RETURNING.
 */
final class QueryBuilder
{
    private const array OPERATORS = ['=', '!=', '<>', '<', '<=', '>', '>=', 'LIKE', 'ILIKE'];

    /** @var list<string> */
    private array $columns = ['*'];

    /** @var list<array{type: string, table: string, left: string, operator: string, right: string}> */
    private array $joins = [];

    /** @var list<array{boolean: 'AND'|'OR', sql: string, bindings: list<mixed>}> */
    private array $wheres = [];

    /** @var list<string> */
    private array $groups = [];

    /** @var list<array{sql: string, bindings: list<mixed>}> */
    private array $havings = [];

    /** @var list<array{column: string, direction: 'ASC'|'DESC'}> */
    private array $orders = [];

    private ?int $limit = null;
    private ?int $offset = null;

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
        return $this->addWhere('AND', $column, $operator, $value);
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
        $bindings = [];
        $select = implode(', ', array_map($this->quoteColumnOrExpression(...), $this->columns));
        $from = $this->dialect->quoteIdentifier($this->table);
        if (null !== $this->alias) {
            $from .= ' AS ' . $this->dialect->quoteIdentifier($this->alias);
        }

        $parts = [sprintf('SELECT %s FROM %s', $select, $from)];

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

        if ([] !== $this->wheres) {
            $whereClauses = [];
            foreach ($this->wheres as $index => $where) {
                $prefix = 0 === $index ? '' : $where['boolean'] . ' ';
                $whereClauses[] = $prefix . $where['sql'];
                array_push($bindings, ...$where['bindings']);
            }
            $parts[] = 'WHERE ' . implode(' ', $whereClauses);
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

        $columns = array_map($this->dialect->quoteIdentifier(...), array_keys($values));
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->dialect->quoteIdentifier($this->table),
            implode(', ', $columns),
            $placeholders,
        );

        if ([] !== $returning && $this->dialect->supportsReturning()) {
            $sql .= ' RETURNING ' . implode(', ', array_map($this->dialect->quoteIdentifier(...), $returning));
        }

        return new Sql($sql, array_values($values));
    }

    /**
     * Compile UPDATE SQL + bindings (requires at least one WHERE clause to prevent accidental full-table wipes).
     *
     * @param array<string, mixed> $values
     * @param list<string>         $returning
     */
    public function toUpdateSql(array $values, array $returning = [], bool $allowUnscoped = false): Sql
    {
        if ([] === $values) {
            throw new DatabaseException('Cannot compile UPDATE with empty values.');
        }
        if ([] === $this->wheres && !$allowUnscoped) {
            throw new DatabaseException('Refusing unscoped UPDATE without WHERE clause. Pass $allowUnscoped = true to override.');
        }

        $sets = [];
        $bindings = [];
        foreach ($values as $column => $value) {
            $sets[] = sprintf('%s = ?', $this->dialect->quoteIdentifier($column));
            $bindings[] = $value;
        }

        $sql = sprintf('UPDATE %s SET %s', $this->dialect->quoteIdentifier($this->table), implode(', ', $sets));
        if ([] !== $this->wheres) {
            $clauses = [];
            foreach ($this->wheres as $i => $w) {
                $clauses[] = (0 === $i ? '' : $w['boolean'] . ' ') . $w['sql'];
                array_push($bindings, ...$w['bindings']);
            }
            $sql .= ' WHERE ' . implode(' ', $clauses);
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
        if ([] === $this->wheres && !$allowUnscoped) {
            throw new DatabaseException('Refusing unscoped DELETE without WHERE clause. Pass $allowUnscoped = true to override.');
        }

        $bindings = [];
        $sql = sprintf('DELETE FROM %s', $this->dialect->quoteIdentifier($this->table));
        if ([] !== $this->wheres) {
            $clauses = [];
            foreach ($this->wheres as $i => $w) {
                $clauses[] = (0 === $i ? '' : $w['boolean'] . ' ') . $w['sql'];
                array_push($bindings, ...$w['bindings']);
            }
            $sql .= ' WHERE ' . implode(' ', $clauses);
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
        $sql = $this->limit(1)->toSelectSql();

        return $this->requireConnection()->first($sql->query, $sql->bindings);
    }

    /** Count matching rows using a clean COUNT(*) query. */
    public function count(): int
    {
        $clone = clone $this;
        $clone->columns = ['COUNT(*) AS aggregate_count'];
        $clone->orders = [];
        $clone->limit = null;
        $clone->offset = null;
        $row = $clone->requireConnection()->first($clone->toSelectSql()->query, $clone->toSelectSql()->bindings);

        return is_array($row) && is_numeric($row['aggregate_count'] ?? null)
            ? (int) $row['aggregate_count']
            : 0;
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
