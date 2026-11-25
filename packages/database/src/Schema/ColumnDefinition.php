<?php

declare(strict_types=1);

namespace Kongono\Database\Schema;

use Kongono\Database\DatabaseException;
use Kongono\Database\Dialect;

/** Column definition builder inside Schema::create() and Schema::table(). */
final class ColumnDefinition
{
    /** @var list<string> */
    private const array ALLOWED_BASE_TYPES = [
        'INT', 'INTEGER', 'BIGINT', 'SMALLINT', 'TINYINT', 'MEDIUMINT',
        'SERIAL', 'BIGSERIAL', 'SMALLSERIAL',
        'VARCHAR', 'CHAR', 'CHARACTER VARYING', 'TEXT', 'TINYTEXT', 'MEDIUMTEXT', 'LONGTEXT',
        'BOOLEAN', 'BOOL',
        'NUMERIC', 'DECIMAL', 'REAL', 'FLOAT', 'DOUBLE', 'DOUBLE PRECISION',
        'DATE', 'TIME', 'TIMESTAMP', 'TIMESTAMPTZ', 'TIMESTAMP WITH TIME ZONE', 'TIMESTAMP WITHOUT TIME ZONE', 'DATETIME',
        'JSON', 'JSONB', 'UUID', 'BYTEA', 'BLOB', 'BINARY', 'VARBINARY', 'INET', 'CIDR', 'VECTOR',
    ];

    public readonly string $type;
    private bool $nullable = false;
    private bool $unique = false;
    private bool $primary = false;
    private mixed $default = null;
    private bool $hasDefault = false;

    public function __construct(
        public readonly string $name,
        string $type,
        public readonly bool $autoIncrement = false,
    ) {
        if (1 === preg_match('/[\x00-\x1f\x7f]/', $type)) {
            throw new DatabaseException('Unsafe SQL column type containing control characters rejected.');
        }
        $normalized = trim((string) preg_replace('/\s+/', ' ', trim($type)));
        if (1 !== preg_match('/\A([A-Za-z][A-Za-z ]*[A-Za-z]|[A-Za-z])(?:\((\d+(?:,\s*\d+)?)\))?\z/', $normalized, $m)) {
            throw new DatabaseException(sprintf('Unsafe SQL column type "%s" rejected.', $type));
        }
        $baseType = strtoupper(trim($m[1]));
        if (!in_array($baseType, self::ALLOWED_BASE_TYPES, true)) {
            throw new DatabaseException(sprintf('Unsafe SQL column type "%s" rejected.', $type));
        }
        $this->type = isset($m[2]) ? $baseType . '(' . $m[2] . ')' : $baseType;
    }

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
        if (null !== $value && !is_scalar($value)) {
            throw new DatabaseException('SQL column default must be null or a scalar value.');
        }
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
            $sql .= ' DEFAULT ' . $this->formatDefault($this->default, $dialect);
        }

        return $sql;
    }

    private function formatDefault(mixed $value, Dialect $dialect): string
    {
        if (null === $value) {
            return 'NULL';
        }
        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }
        if (is_int($value)) {
            return (string) $value;
        }
        if (is_float($value)) {
            if (!is_finite($value)) {
                throw new DatabaseException('Non-finite float cannot be used as SQL column default.');
            }

            return (string) $value;
        }
        if (is_string($value)) {
            if (str_contains($value, "\0")) {
                throw new DatabaseException('SQL column default string cannot contain NUL bytes.');
            }
            $escaped = Dialect::Mysql === $dialect
                ? str_replace(['\\', "'"], ['\\\\', "''"], $value)
                : str_replace("'", "''", $value);

            return "'" . $escaped . "'";
        }

        throw new DatabaseException('Unsupported non-scalar SQL column default.');
    }
}
