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
            $parts = explode('.', $identifier);
            $lastIdx = count($parts) - 1;
            $quoted = [];
            foreach ($parts as $idx => $part) {
                if ('*' === $part) {
                    if ($idx !== $lastIdx) {
                        throw new DatabaseException(sprintf('Unsafe SQL identifier "%s".', $identifier));
                    }
                    $quoted[] = '*';
                    continue;
                }
                $quoted[] = $this->quoteSinglePart($part, $identifier);
            }

            return implode('.', $quoted);
        }

        return $this->quoteSinglePart($identifier, $identifier);
    }

    private function quoteSinglePart(string $part, string $original): string
    {
        if (1 !== preg_match('/\A[a-zA-Z_][a-zA-Z0-9_]*\z/', $part)) {
            throw new DatabaseException(sprintf('Unsafe SQL identifier "%s".', $original));
        }

        return match ($this) {
            self::Mysql => '`' . $part . '`',
            self::Postgres, self::Sqlite => '"' . $part . '"',
        };
    }

    public function supportsReturning(): bool
    {
        return self::Postgres === $this || self::Sqlite === $this;
    }
}
