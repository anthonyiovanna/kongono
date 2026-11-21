<?php

declare(strict_types=1);

namespace Kongono\Database;

enum Dialect: string
{
    case Postgres = 'pgsql';
    case Sqlite = 'sqlite';
    case Mysql = 'mysql';

    public function quoteIdentifier(string $identifier): string
    {
        if ('*' === $identifier) {
            return '*';
        }
        if (str_contains($identifier, '.')) {
            return implode('.', array_map($this->quoteIdentifier(...), explode('.', $identifier)));
        }
        if (1 !== preg_match('/\A[a-zA-Z_][a-zA-Z0-9_]*\z/', $identifier)) {
            throw new DatabaseException(sprintf('Unsafe SQL identifier "%s".', $identifier));
        }

        return match ($this) {
            self::Mysql => '`' . $identifier . '`',
            self::Postgres, self::Sqlite => '"' . $identifier . '"',
        };
    }

    public function supportsReturning(): bool
    {
        return self::Postgres === $this || self::Sqlite === $this;
    }
}
