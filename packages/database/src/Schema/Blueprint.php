<?php

declare(strict_types=1);

namespace Kongono\Database\Schema;

use Kongono\Database\DatabaseException;
use Kongono\Database\Dialect;
use Kongono\Database\RowLevelSecurity;

/** Clean, dialect-portable table DDL blueprint for Schema::create() and Schema::table(). */
final class Blueprint
{
    /** @var list<string> */
    private const array ALLOWED_FOREIGN_ACTIONS = [
        'CASCADE',
        'RESTRICT',
        'SET NULL',
        'NO ACTION',
        'SET DEFAULT',
    ];

    /** @var list<ColumnDefinition> */
    private array $columns = [];

    /** @var list<array{columns: list<string>, unique: bool, concurrently: bool, name: ?string}> */
    private array $indexes = [];

    /** @var list<array{column: string, referencesTable: string, referencesColumn: string, onDelete: string, onUpdate: ?string, name: ?string}> */
    private array $foreignKeys = [];

    /** @var list<string> */
    private array $droppedColumns = [];

    /** @var list<array{from: string, to: string}> */
    private array $renamedColumns = [];

    /** @var list<array{name: string, concurrently: bool}> */
    private array $droppedIndexes = [];

    /** @var list<string> */
    private array $droppedForeignKeys = [];

    /** @var ?array{tenantColumn: string, settingName: string, policyName: ?string} */
    private ?array $rls = null;

    public function __construct(
        public readonly string $table,
        private readonly Dialect $dialect = Dialect::Postgres,
    ) {}

    public function id(string $name = 'id'): ColumnDefinition
    {
        $col = new ColumnDefinition($name, 'BIGINT', autoIncrement: true);
        $this->columns[] = $col;

        return $col;
    }

    public function string(string $name, int $length = 255): ColumnDefinition
    {
        $col = new ColumnDefinition($name, sprintf('VARCHAR(%d)', max(1, $length)));
        $this->columns[] = $col;

        return $col;
    }

    public function text(string $name): ColumnDefinition
    {
        $col = new ColumnDefinition($name, 'TEXT');
        $this->columns[] = $col;

        return $col;
    }

    public function integer(string $name): ColumnDefinition
    {
        $col = new ColumnDefinition($name, 'INTEGER');
        $this->columns[] = $col;

        return $col;
    }

    public function bigInteger(string $name): ColumnDefinition
    {
        $col = new ColumnDefinition($name, 'BIGINT');
        $this->columns[] = $col;

        return $col;
    }

    public function boolean(string $name): ColumnDefinition
    {
        $col = new ColumnDefinition($name, 'BOOLEAN');
        $this->columns[] = $col;

        return $col;
    }

    public function timestamp(string $name): ColumnDefinition
    {
        $type = Dialect::Postgres === $this->dialect ? 'TIMESTAMPTZ' : 'TIMESTAMP';
        $col = new ColumnDefinition($name, $type);
        $this->columns[] = $col;

        return $col;
    }

    public function timestamps(): self
    {
        $this->timestamp('created_at');
        $this->timestamp('updated_at')->nullable();

        return $this;
    }

    /** Add a nullable soft-delete timestamp column (`deleted_at` by default). */
    public function softDeletes(string $column = 'deleted_at'): ColumnDefinition
    {
        return $this->timestamp($column)->nullable();
    }

    /** Drop a soft-delete column during `Schema::table()` alterations. */
    public function dropSoftDeletes(string $column = 'deleted_at'): self
    {
        return $this->dropColumn($column);
    }

    /** Dialect-portable UUID column (`UUID` on Postgres, `CHAR(36)` on MySQL, `TEXT` on SQLite). */
    public function uuid(string $name): ColumnDefinition
    {
        $type = match ($this->dialect) {
            Dialect::Postgres => 'UUID',
            Dialect::Mysql => 'CHAR(36)',
            Dialect::Sqlite => 'TEXT',
        };
        $col = new ColumnDefinition($name, $type);
        $this->columns[] = $col;

        return $col;
    }

    /** Dialect-portable JSON column (`JSONB` on Postgres, `JSON` on MySQL, `TEXT` on SQLite). */
    public function json(string $name): ColumnDefinition
    {
        $type = match ($this->dialect) {
            Dialect::Postgres => 'JSONB',
            Dialect::Mysql => 'JSON',
            Dialect::Sqlite => 'TEXT',
        };
        $col = new ColumnDefinition($name, $type);
        $this->columns[] = $col;

        return $col;
    }

    /** Exact-precision fixed-point decimal column (`NUMERIC(p,s)` on Postgres/SQLite, `DECIMAL(p,s)` on MySQL). */
    public function decimal(string $name, int $precision = 12, int $scale = 2): ColumnDefinition
    {
        if ($precision < 1 || $precision > 38) {
            throw new DatabaseException(sprintf('Decimal precision must be between 1 and 38 (got %d).', $precision));
        }
        if ($scale < 0 || $scale > $precision) {
            throw new DatabaseException(sprintf('Decimal scale must be between 0 and precision %d (got %d).', $precision, $scale));
        }

        $base = Dialect::Mysql === $this->dialect ? 'DECIMAL' : 'NUMERIC';
        $col = new ColumnDefinition($name, sprintf('%s(%d,%d)', $base, $precision, $scale));
        $this->columns[] = $col;

        return $col;
    }

    /**
     * Add a validated FOREIGN KEY constraint referencing another table and column.
     */
    public function foreignKey(
        string $column,
        string $referencesTable,
        string $referencesColumn = 'id',
        string $onDelete = 'RESTRICT',
        ?string $onUpdate = null,
        ?string $name = null,
    ): self {
        $normalizedDelete = $this->validateForeignAction($onDelete, 'ON DELETE');
        $normalizedUpdate = null !== $onUpdate ? $this->validateForeignAction($onUpdate, 'ON UPDATE') : null;

        $this->foreignKeys[] = [
            'column' => $column,
            'referencesTable' => $referencesTable,
            'referencesColumn' => $referencesColumn,
            'onDelete' => $normalizedDelete,
            'onUpdate' => $normalizedUpdate,
            'name' => $name,
        ];

        return $this;
    }

    /**
     * Convenience helper: declare a `BIGINT` column and attach a `FOREIGN KEY` constraint in one call.
     */
    public function foreignId(
        string $column,
        string $referencesTable,
        string $referencesColumn = 'id',
        string $onDelete = 'RESTRICT',
        ?string $onUpdate = null,
        ?string $name = null,
    ): ColumnDefinition {
        $col = $this->bigInteger($column);
        $this->foreignKey($column, $referencesTable, $referencesColumn, $onDelete, $onUpdate, $name);

        return $col;
    }

    /** @param list<string> $columns */
    public function index(array $columns, bool $unique = false, bool $concurrently = false, ?string $name = null): self
    {
        if ([] === $columns) {
            throw new DatabaseException('Index definition must specify at least one column.');
        }

        $this->indexes[] = [
            'columns' => $columns,
            'unique' => $unique,
            'concurrently' => $concurrently,
            'name' => $name,
        ];

        return $this;
    }

    /** Drop one or more columns inside `Schema::table()`. */
    public function dropColumn(string ...$names): self
    {
        if ([] === $names) {
            throw new DatabaseException('dropColumn() requires at least one column name.');
        }
        foreach ($names as $name) {
            $this->droppedColumns[] = $name;
        }

        return $this;
    }

    /** Rename a column inside `Schema::table()`. */
    public function renameColumn(string $from, string $to): self
    {
        $this->renamedColumns[] = ['from' => $from, 'to' => $to];

        return $this;
    }

    /**
     * Drop an index by explicit name or by its column list inside `Schema::table()`.
     *
     * @param string|list<string> $columnsOrName
     */
    public function dropIndex(string|array $columnsOrName, bool $concurrently = false): self
    {
        if (is_array($columnsOrName)) {
            if ([] === $columnsOrName) {
                throw new DatabaseException('dropIndex() requires an index name or non-empty list of columns.');
            }
            $name = sprintf('idx_%s_%s', $this->table, implode('_', $columnsOrName));
        } else {
            $name = $columnsOrName;
        }

        $this->droppedIndexes[] = [
            'name' => $name,
            'concurrently' => $concurrently,
        ];

        return $this;
    }

    /** Drop a foreign key constraint by name inside `Schema::table()`. */
    public function dropForeign(string $constraintName): self
    {
        $this->droppedForeignKeys[] = $constraintName;

        return $this;
    }

    /**
     * Enable & force fail-closed PostgreSQL Row-Level Security (RLS) on this table when migrating on Postgres.
     */
    public function rowLevelSecurity(
        string $tenantColumn = 'workspace_id',
        string $settingName = RowLevelSecurity::DEFAULT_SETTING,
        ?string $policyName = null,
    ): self {
        $this->rls = [
            'tenantColumn' => $tenantColumn,
            'settingName' => $settingName,
            'policyName' => $policyName,
        ];

        return $this;
    }

    /** @return list<string> Compiled CREATE TABLE DDL statements */
    public function toSqlStatements(): array
    {
        if ([] !== $this->droppedColumns || [] !== $this->renamedColumns || [] !== $this->droppedIndexes || [] !== $this->droppedForeignKeys) {
            throw new DatabaseException('Alter-table operations (dropColumn, renameColumn, dropIndex, dropForeign) must be used inside Schema::table(), not Schema::create().');
        }

        $tableQuoted = $this->dialect->quoteIdentifier($this->table);
        $definitions = array_map(fn(ColumnDefinition $c): string => $c->toSql($this->dialect), $this->columns);
        foreach ($this->foreignKeys as $fk) {
            $definitions[] = $this->compileForeignKeyClause($fk);
        }

        $statements = [
            sprintf('CREATE TABLE IF NOT EXISTS %s (%s)', $tableQuoted, implode(', ', $definitions)),
        ];

        foreach ($this->indexes as $idx) {
            $statements[] = $this->compileCreateIndexSql($tableQuoted, $idx);
        }

        if (null !== $this->rls && Dialect::Postgres === $this->dialect) {
            foreach (RowLevelSecurity::enableSql($this->table, $this->rls['tenantColumn'], $this->rls['settingName'], $this->rls['policyName']) as $rlsSql) {
                $statements[] = $rlsSql;
            }
        }

        return $statements;
    }

    /** @return list<string> Compiled ALTER TABLE / index / RLS DDL statements for Schema::table() */
    public function toAlterSqlStatements(): array
    {
        $tableQuoted = $this->dialect->quoteIdentifier($this->table);
        $statements = [];

        // 1. Add new columns first so new FKs, indexes, and RLS policies can reference them
        foreach ($this->columns as $col) {
            $statements[] = sprintf('ALTER TABLE %s ADD COLUMN %s', $tableQuoted, $col->toSql($this->dialect));
        }

        // 2. Rename columns before creating new indexes/constraints on the renamed names
        foreach ($this->renamedColumns as $rename) {
            $statements[] = sprintf(
                'ALTER TABLE %s RENAME COLUMN %s TO %s',
                $tableQuoted,
                $this->dialect->quoteIdentifier($rename['from']),
                $this->dialect->quoteIdentifier($rename['to']),
            );
        }

        // 3. Add foreign key constraints
        foreach ($this->foreignKeys as $fk) {
            if (Dialect::Sqlite === $this->dialect) {
                throw new DatabaseException('SQLite does not support adding foreign key constraints via ALTER TABLE; declare them inside Schema::create().');
            }
            $statements[] = sprintf('ALTER TABLE %s ADD %s', $tableQuoted, $this->compileForeignKeyClause($fk));
        }

        // 4. Create indexes
        foreach ($this->indexes as $idx) {
            $statements[] = $this->compileCreateIndexSql($tableQuoted, $idx);
        }

        // 5. Drop indexes before dropping columns that may belong to them
        foreach ($this->droppedIndexes as $dropIdx) {
            $quotedIdx = $this->dialect->quoteIdentifier($dropIdx['name']);
            $statements[] = match ($this->dialect) {
                Dialect::Postgres => sprintf(
                    'DROP INDEX %sIF EXISTS %s',
                    $dropIdx['concurrently'] ? 'CONCURRENTLY ' : '',
                    $quotedIdx,
                ),
                Dialect::Sqlite => sprintf('DROP INDEX IF EXISTS %s', $quotedIdx),
                Dialect::Mysql => sprintf('DROP INDEX %s ON %s', $quotedIdx, $tableQuoted),
            };
        }

        // 6. Drop foreign keys before dropping referenced columns
        foreach ($this->droppedForeignKeys as $fkName) {
            $quotedFk = $this->dialect->quoteIdentifier($fkName);
            $statements[] = match ($this->dialect) {
                Dialect::Postgres => sprintf('ALTER TABLE %s DROP CONSTRAINT IF EXISTS %s', $tableQuoted, $quotedFk),
                Dialect::Mysql => sprintf('ALTER TABLE %s DROP FOREIGN KEY %s', $tableQuoted, $quotedFk),
                Dialect::Sqlite => throw new DatabaseException('SQLite does not support dropping foreign key constraints via ALTER TABLE.'),
            };
        }

        // 7. Drop columns last
        foreach ($this->droppedColumns as $colName) {
            $statements[] = sprintf('ALTER TABLE %s DROP COLUMN %s', $tableQuoted, $this->dialect->quoteIdentifier($colName));
        }

        // 8. Optional PostgreSQL Row-Level Security on existing table
        if (null !== $this->rls && Dialect::Postgres === $this->dialect) {
            foreach (RowLevelSecurity::enableSql($this->table, $this->rls['tenantColumn'], $this->rls['settingName'], $this->rls['policyName']) as $rlsSql) {
                $statements[] = $rlsSql;
            }
        }

        return $statements;
    }

    /**
     * @param array{columns: list<string>, unique: bool, concurrently: bool, name: ?string} $idx
     */
    private function compileCreateIndexSql(string $tableQuoted, array $idx): string
    {
        $idxName = $idx['name'] ?? sprintf('idx_%s_%s', $this->table, implode('_', $idx['columns']));
        $cols = implode(', ', array_map($this->dialect->quoteIdentifier(...), $idx['columns']));
        $unique = $idx['unique'] ? 'UNIQUE ' : '';
        $quotedName = $this->dialect->quoteIdentifier($idxName);

        return match ($this->dialect) {
            Dialect::Postgres => sprintf(
                'CREATE %sINDEX %sIF NOT EXISTS %s ON %s (%s)',
                $unique,
                $idx['concurrently'] ? 'CONCURRENTLY ' : '',
                $quotedName,
                $tableQuoted,
                $cols,
            ),
            Dialect::Sqlite => sprintf(
                'CREATE %sINDEX IF NOT EXISTS %s ON %s (%s)',
                $unique,
                $quotedName,
                $tableQuoted,
                $cols,
            ),
            Dialect::Mysql => sprintf(
                'CREATE %sINDEX %s ON %s (%s)',
                $unique,
                $quotedName,
                $tableQuoted,
                $cols,
            ),
        };
    }

    /**
     * @param array{column: string, referencesTable: string, referencesColumn: string, onDelete: string, onUpdate: ?string, name: ?string} $fk
     */
    private function compileForeignKeyClause(array $fk): string
    {
        $constraintName = $fk['name'] ?? sprintf('fk_%s_%s', $this->table, $fk['column']);
        $sql = sprintf(
            'CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s (%s) ON DELETE %s',
            $this->dialect->quoteIdentifier($constraintName),
            $this->dialect->quoteIdentifier($fk['column']),
            $this->dialect->quoteIdentifier($fk['referencesTable']),
            $this->dialect->quoteIdentifier($fk['referencesColumn']),
            $fk['onDelete'],
        );

        if (null !== $fk['onUpdate']) {
            $sql .= ' ON UPDATE ' . $fk['onUpdate'];
        }

        return $sql;
    }

    private function validateForeignAction(string $action, string $clause): string
    {
        $normalized = strtoupper(trim((string) preg_replace('/\s+/', ' ', trim($action))));
        if (!in_array($normalized, self::ALLOWED_FOREIGN_ACTIONS, true)) {
            throw new DatabaseException(sprintf(
                'Unsupported foreign key %s action "%s". Allowed: %s.',
                $clause,
                $action,
                implode(', ', self::ALLOWED_FOREIGN_ACTIONS),
            ));
        }

        return $normalized;
    }
}
