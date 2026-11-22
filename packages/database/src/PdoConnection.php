<?php

declare(strict_types=1);

namespace Kongono\Database;

use Closure;
use PDO;
use PDOException;
use Throwable;

/** Strict PDO connection supporting PostgreSQL, SQLite, and MySQL with real prepared statements. */
final class PdoConnection implements ConnectionInterface
{
    /** @var list<Closure(string, float): void> */
    private array $queryListeners = [];

    public function __construct(
        private readonly PDO $pdo,
        private readonly Dialect $dialect = Dialect::Postgres,
    ) {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }

    /**
     * Register a query telemetry/profiling listener invoked after each executed statement.
     *
     * @param Closure(string, float): void $listener Receives ($sql, $durationMs)
     */
    public function onQuery(Closure $listener): self
    {
        $this->queryListeners[] = $listener;

        return $this;
    }

    public static function pgsql(
        string $host,
        int $port,
        string $database,
        string $username,
        #[\SensitiveParameter]
        string $password,
    ): self {
        self::assertSafeDsnPart($host, 'host');
        self::assertSafeDsnPart($database, 'database');
        $dsn = sprintf('pgsql:host=%s;port=%d;dbname=%s', $host, $port, $database);

        return new self(new PDO($dsn, $username, $password), Dialect::Postgres);
    }

    public static function sqlite(string $path = ':memory:'): self
    {
        return new self(new PDO('sqlite:' . $path), Dialect::Sqlite);
    }

    /** Connect from a standard DATABASE_URL (postgres://, mysql://, or sqlite://). */
    public static function fromUrl(#[\SensitiveParameter] string $url): self
    {
        $parts = parse_url($url);
        if (false === $parts || !isset($parts['scheme'])) {
            throw new DatabaseException('Invalid DATABASE_URL format.');
        }

        $scheme = strtolower($parts['scheme']);
        if ('sqlite' === $scheme) {
            $path = ltrim($parts['path'] ?? ':memory:', '/');

            return self::sqlite('' === $path ? ':memory:' : $path);
        }

        $host = $parts['host'] ?? '127.0.0.1';
        $dbName = ltrim($parts['path'] ?? '', '/');
        $user = isset($parts['user']) ? rawurldecode($parts['user']) : '';
        $pass = isset($parts['pass']) ? rawurldecode($parts['pass']) : '';

        if ('postgres' === $scheme || 'postgresql' === $scheme || 'pgsql' === $scheme) {
            return self::pgsql($host, $parts['port'] ?? 5432, $dbName, $user, $pass);
        }

        if ('mysql' === $scheme) {
            self::assertSafeDsnPart($host, 'host');
            self::assertSafeDsnPart($dbName, 'database');
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $parts['port'] ?? 3306, $dbName);

            return new self(new PDO($dsn, $user, $pass), Dialect::Mysql);
        }

        throw new DatabaseException(sprintf('Unsupported DATABASE_URL scheme "%s".', $scheme));
    }

    public function dialect(): Dialect
    {
        return $this->dialect;
    }

    public function query(string $sql, array $bindings = []): array
    {
        $start = [] !== $this->queryListeners ? hrtime(true) : 0;

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($bindings);
            /** @var list<array<string, mixed>> $rows */
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->notifyQuery($sql, $start);

            return $rows;
        } catch (PDOException $e) {
            throw new DatabaseException('Database query failed: ' . $e->getMessage(), previous: $e);
        }
    }

    public function first(string $sql, array $bindings = []): ?array
    {
        $start = [] !== $this->queryListeners ? hrtime(true) : 0;

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($bindings);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->notifyQuery($sql, $start);
            if (!is_array($row)) {
                return null;
            }
            /** @var array<string, mixed> $typed */
            $typed = $row;

            return $typed;
        } catch (PDOException $e) {
            throw new DatabaseException('Database query failed: ' . $e->getMessage(), previous: $e);
        }
    }

    public function execute(string $sql, array $bindings = []): int
    {
        $start = [] !== $this->queryListeners ? hrtime(true) : 0;

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($bindings);
            $count = $stmt->rowCount();
            $this->notifyQuery($sql, $start);

            return $count;
        } catch (PDOException $e) {
            throw new DatabaseException('Database execution failed: ' . $e->getMessage(), previous: $e);
        }
    }

    private function notifyQuery(string $sql, int $startNs): void
    {
        if ([] === $this->queryListeners) {
            return;
        }
        $durationMs = round((hrtime(true) - $startNs) / 1_000_000, 3);
        foreach ($this->queryListeners as $listener) {
            $listener($sql, $durationMs);
        }
    }

    public function table(string $table, ?string $alias = null): QueryBuilder
    {
        return new QueryBuilder($table, $this->dialect, $this, $alias);
    }

    public function transaction(Closure $callback): mixed
    {
        if (!$this->pdo->beginTransaction()) {
            throw new DatabaseException('Unable to begin database transaction.');
        }

        try {
            $result = $callback($this);
            if (!$this->pdo->commit()) {
                throw new DatabaseException('Database transaction commit returned false.');
            }

            return $result;
        } catch (Throwable $failure) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $failure;
        }
    }

    private static function assertSafeDsnPart(string $value, string $field): void
    {
        if ('' === $value || false !== strpbrk($value, ";\r\n\0")) {
            throw new DatabaseException(sprintf('Unsafe DSN %s parameter rejected.', $field));
        }
    }
}
