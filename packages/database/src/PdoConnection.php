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
    /** @var array<int, Closure(string, float): void> */
    private array $queryListeners = [];

    private int $nextListenerId = 0;

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
        $this->queryListeners[++$this->nextListenerId] = $listener;

        return $this;
    }

    /**
     * Register a scoped query listener and return an unsubscribe closure (ideal for `OperationScope::defer()`).
     *
     * @param Closure(string, float): void $listener
     * @return Closure(): void
     */
    public function listenQuery(Closure $listener): Closure
    {
        $id = ++$this->nextListenerId;
        $this->queryListeners[$id] = $listener;

        return function () use ($id): void {
            unset($this->queryListeners[$id]);
        };
    }

    public function clearQueryListeners(): void
    {
        $this->queryListeners = [];
    }

    public static function pgsql(
        string $host,
        int $port,
        string $database,
        string $username,
        #[\SensitiveParameter]
        string $password,
        ?string $sslMode = null,
    ): self {
        self::assertSafeDsnPart($host, 'host');
        self::assertSafeDsnPart($database, 'database');
        self::assertValidPort($port);
        $dsn = sprintf('pgsql:host=%s;port=%d;dbname=%s', $host, $port, $database);
        if (null !== $sslMode && '' !== trim($sslMode)) {
            $mode = strtolower(trim($sslMode));
            if (!in_array($mode, ['disable', 'allow', 'prefer', 'require', 'verify-ca', 'verify-full'], true)) {
                throw new DatabaseException(sprintf('Unsupported PostgreSQL sslmode "%s".', $sslMode));
            }
            $dsn .= ';sslmode=' . $mode;
        }

        return new self(new PDO($dsn, $username, $password), Dialect::Postgres);
    }

    public static function sqlite(string $path = ':memory:'): self
    {
        if (str_contains($path, "\0")) {
            throw new DatabaseException('Unsafe SQLite path containing NUL byte rejected.');
        }

        return new self(new PDO('sqlite:' . $path), Dialect::Sqlite);
    }

    /** Connect from a standard DATABASE_URL (postgres://, mysql://, or sqlite://) preserving TLS/SSL query options. */
    public static function fromUrl(#[\SensitiveParameter] string $url): self
    {
        $parts = parse_url($url);
        if (false === $parts || !isset($parts['scheme'])) {
            throw new DatabaseException('Invalid DATABASE_URL format.');
        }

        $scheme = strtolower($parts['scheme']);
        if ('sqlite' === $scheme) {
            if (str_starts_with(strtolower($url), 'sqlite:///')) {
                $rawPath = substr($url, strlen('sqlite://'));
                $qPos = strpos($rawPath, '?');
                $path = false === $qPos ? $rawPath : substr($rawPath, 0, $qPos);
            } else {
                $path = ltrim($parts['path'] ?? ':memory:', '/');
            }

            return self::sqlite('' === $path ? ':memory:' : $path);
        }

        $host = $parts['host'] ?? '127.0.0.1';
        $dbName = ltrim($parts['path'] ?? '', '/');
        $user = isset($parts['user']) ? rawurldecode($parts['user']) : '';
        $pass = isset($parts['pass']) ? rawurldecode($parts['pass']) : '';
        $query = [];
        if (isset($parts['query']) && '' !== $parts['query']) {
            parse_str($parts['query'], $query);
        }

        if ('postgres' === $scheme || 'postgresql' === $scheme || 'pgsql' === $scheme) {
            $sslMode = is_string($query['sslmode'] ?? null) ? $query['sslmode'] : null;

            return self::pgsql($host, $parts['port'] ?? 5432, $dbName, $user, $pass, $sslMode);
        }

        if ('mysql' === $scheme) {
            $mysqlPort = $parts['port'] ?? 3306;
            self::assertSafeDsnPart($host, 'host');
            self::assertSafeDsnPart($dbName, 'database');
            self::assertValidPort($mysqlPort);
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $mysqlPort, $dbName);
            $options = [];
            $sslModeRaw = is_string($query['ssl-mode'] ?? null) ? $query['ssl-mode'] : (is_string($query['sslmode'] ?? null) ? $query['sslmode'] : null);
            $sslCa = is_string($query['sslca'] ?? null) ? $query['sslca'] : (is_string($query['sslrootcert'] ?? null) ? $query['sslrootcert'] : null);
            if (null !== $sslCa && '' !== trim($sslCa)) {
                if (str_contains($sslCa, "\0")) {
                    throw new DatabaseException('Unsafe MySQL SSL CA path.');
                }
                if (defined('PDO::MYSQL_ATTR_SSL_CA')) {
                    $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCa;
                }
            }
            if (null !== $sslModeRaw && '' !== trim($sslModeRaw)) {
                $mode = strtoupper(trim($sslModeRaw));
                if (!in_array($mode, ['DISABLED', 'PREFERRED', 'REQUIRED', 'VERIFY_CA', 'VERIFY_IDENTITY'], true)) {
                    throw new DatabaseException(sprintf('Unsupported MySQL ssl-mode "%s".', $sslModeRaw));
                }
                if (in_array($mode, ['REQUIRED', 'VERIFY_CA', 'VERIFY_IDENTITY'], true)) {
                    if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
                        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = 'REQUIRED' !== $mode;
                    }
                    if (!isset($options[PDO::MYSQL_ATTR_SSL_CA]) && 'REQUIRED' !== $mode) {
                        throw new DatabaseException(sprintf('MySQL ssl-mode=%s requires sslca/sslrootcert in DATABASE_URL.', $mode));
                    }
                }
            }

            return new self(new PDO($dsn, $user, $pass, $options), Dialect::Mysql);
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
        $pattern = 'host' === $field
            ? '/\A[a-zA-Z0-9._:\-\[\]]+\z/'
            : '/\A[^\s;\'"\\\\=\x00-\x1f\x7f]+\z/';
        if ('' === $value || 1 !== preg_match($pattern, $value)) {
            throw new DatabaseException(sprintf('Unsafe DSN %s parameter rejected.', $field));
        }
    }

    private static function assertValidPort(int $port): void
    {
        if ($port < 1 || $port > 65535) {
            throw new DatabaseException(sprintf('Invalid database port %d.', $port));
        }
    }
}
