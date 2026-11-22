<?php

declare(strict_types=1);

namespace Kongono\Database\Schema;

use Kongono\Database\Dialect;
use Kongono\Database\RowLevelSecurity;

/** Clean, dialect-portable table DDL blueprint for migrations. */
final class Blueprint
{
    /** @var list<ColumnDefinition> */
    private array $columns = [];

    /** @var list<array{columns: list<string>, unique: bool}> */
    private array $indexes = [];

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

    /** @param list<string> $columns */
    public function index(array $columns, bool $unique = false): self
    {
        $this->indexes[] = ['columns' => $columns, 'unique' => $unique];

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

    /** @return list<string> Compiled DDL statements */
    public function toSqlStatements(): array
    {
        $tableQuoted = $this->dialect->quoteIdentifier($this->table);
        $columnSql = array_map(fn(ColumnDefinition $c): string => $c->toSql($this->dialect), $this->columns);
        $statements = [
            sprintf('CREATE TABLE IF NOT EXISTS %s (%s)', $tableQuoted, implode(', ', $columnSql)),
        ];

        foreach ($this->indexes as $idx) {
            $idxName = sprintf('idx_%s_%s', $this->table, implode('_', $idx['columns']));
            $cols = implode(', ', array_map($this->dialect->quoteIdentifier(...), $idx['columns']));
            $statements[] = sprintf(
                'CREATE %sINDEX IF NOT EXISTS %s ON %s (%s)',
                $idx['unique'] ? 'UNIQUE ' : '',
                $this->dialect->quoteIdentifier($idxName),
                $tableQuoted,
                $cols,
            );
        }

        if (null !== $this->rls && Dialect::Postgres === $this->dialect) {
            foreach (RowLevelSecurity::enableSql($this->table, $this->rls['tenantColumn'], $this->rls['settingName'], $this->rls['policyName']) as $rlsSql) {
                $statements[] = $rlsSql;
            }
        }

        return $statements;
    }
}
