<?php

declare(strict_types=1);

namespace Kongono\Database\Schema;

use Kongono\Database\Dialect;

/** Column definition builder inside Schema::create(). */
final class ColumnDefinition
{
    private bool $nullable = false;
    private bool $unique = false;
    private bool $primary = false;
    private mixed $default = null;
    private bool $hasDefault = false;

    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly bool $autoIncrement = false,
    ) {}

    public function nullable(bool $value = true): self
    {
        $this->nullable = $value;

        return $this;
    }

    public function unique(bool $value = true): self
    {
        $this->unique = $value;

        return $this;
    }

    public function primary(bool $value = true): self
    {
        $this->primary = $value;

        return $this;
    }

    public function default(mixed $value): self
    {
        $this->default = $value;
        $this->hasDefault = true;

        return $this;
    }

    public function toSql(Dialect $dialect): string
    {
        $quoted = $dialect->quoteIdentifier($this->name);
        if ($this->autoIncrement) {
            return match ($dialect) {
                Dialect::Postgres => $quoted . ' BIGSERIAL PRIMARY KEY',
                Dialect::Sqlite => $quoted . ' INTEGER PRIMARY KEY AUTOINCREMENT',
                Dialect::Mysql => $quoted . ' BIGINT AUTO_INCREMENT PRIMARY KEY',
            };
        }

        $sql = $quoted . ' ' . $this->type;
        if ($this->primary) {
            $sql .= ' PRIMARY KEY';
        }
        $sql .= $this->nullable ? ' NULL' : ' NOT NULL';
        if ($this->unique) {
            $sql .= ' UNIQUE';
        }
        if ($this->hasDefault) {
            $sql .= ' DEFAULT ' . $this->formatDefault($this->default);
        }

        return $sql;
    }

    private function formatDefault(mixed $value): string
    {
        if (null === $value) {
            return 'NULL';
        }
        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }
        if (is_string($value)) {
            return "'" . str_replace("'", "''", $value) . "'";
        }

        return 'NULL';
    }
}
