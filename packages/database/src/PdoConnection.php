<?php

declare(strict_types=1);

namespace Kongono\Database;

use Closure;
use PDO;
use PDOException;
use PDOStatement;
use Throwable;

/**
 * Strict PDO connection supporting PostgreSQL, SQLite, and MySQL with real prepared statements,
 * bounded LRU prepared-statement reuse, and automatic transparent reconnect on idle transport loss
 * in persistent workers (FrankenPHP / RoadRunner).
 */
final class PdoConnection implements ConnectionInterface
{
    private const int MAX_CACHED_STATEMENTS = 256;

    /** @var array<int, Closure(string, float): void> */
    private array $queryListeners = [];

    private int $nextListenerId = 0;

    /** @var array<string, PDOStatement> */
    private array $statementCache = [];

    private int $transactionDepth = 0;

    private PDO $pdo;

    /** @var ?Closure(): PDO */
    private ?Closure $connector;

    /** @param ?Closure(): PDO $connector Optional factory for automatic reconnect after idle socket drop */
    public function __construct(
        PDO $pdo,
        private readonly Dialect $dialect = Dialect::Postgres,
        ?Closure $connector = null,
    ) {
        $this->pdo = $pdo;
        $this->connector = $connector;
        $this->configurePdo($this->pdo);
    }

    /** Re-establish the underlying PDO connection and clear cached prepared statements. */
    public function reconnect(): void
    {
        if (null === $this->connector) {
            throw new DatabaseException('PdoConnection cannot reconnect without a connector factory.');
        }
        $this->statementCache = [];
        $this->transactionDepth = 0;
        $this->pdo = ($this->connector)();
        $this->configurePdo($this->pdo);
    }

    private function configurePdo(PDO $pdo): void
    {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
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

        $connector = static fn(): PDO => new PDO($dsn, $username, $password);

        return new self($connector(), Dialect::Postgres, $connector);
    }

    public static function sqlite(string $path = ':memory:'): self
    {
        if (str_contains($path, "\0")) {
            throw new DatabaseException('Unsafe SQLite path containing NUL byte rejected.');
        }

        $connector = ':memory:' === $path ? null : static fn(): PDO => new PDO('sqlite:' . $path);

        return new self(new PDO('sqlite:' . $path), Dialect::Sqlite, $connector);
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

            $connector = static fn(): PDO => new PDO($dsn, $user, $pass, $options);

            return new self($connector(), Dialect::Mysql, $connector);
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

        /** @var list<array<string, mixed>> $rows */
        $rows = $this->runWithReconnect($sql, $bindings, static function (PDOStatement $stmt): array {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }, 'Database query failed: ');
        $this->notifyQuery($sql, $start);

        return $rows;
    }

    public function first(string $sql, array $bindings = []): ?array
    {
        $start = [] !== $this->queryListeners ? hrtime(true) : 0;

        $row = $this->runWithReconnect($sql, $bindings, static function (PDOStatement $stmt): mixed {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }, 'Database query failed: ');
        $this->notifyQuery($sql, $start);
        if (!is_array($row)) {
            return null;
        }
        /** @var array<string, mixed> $typed */
        $typed = $row;

        return $typed;
    }

    public function execute(string $sql, array $bindings = []): int
    {
        $start = [] !== $this->queryListeners ? hrtime(true) : 0;

        $count = $this->runWithReconnect($sql, $bindings, static function (PDOStatement $stmt): int {
            return $stmt->rowCount();
        }, 'Database execution failed: ');
        $this->notifyQuery($sql, $start);

        return $count;
    }

    /**
     * @template T
     * @param list<mixed>                 $bindings
     * @param Closure(PDOStatement): T    $extractor
     * @return T
     */
    private function runWithReconnect(string $sql, array $bindings, Closure $extractor, string $errorPrefix): mixed
    {
        $canRetry = null !== $this->connector && !$this->pdo->inTransaction();

        for ($attempt = 1; $attempt <= ($canRetry ? 2 : 1); ++$attempt) {
            try {
                $stmt = $this->prepareCached($sql);
                $stmt->execute($bindings);
                $result = $extractor($stmt);
                if (PDOStatement::class === $stmt::class) {
                    $stmt->closeCursor();
                }

                return $result;
            } catch (PDOException $e) {
                unset($this->statementCache[$sql]);
                if ($attempt === 1 && $canRetry && self::isConnectionLost($e)) {
                    $this->reconnect();
                    continue;
                }

                throw new DatabaseException($errorPrefix . $e->getMessage(), previous: $e);
            }
        }

        throw new DatabaseException($errorPrefix . 'exhausted connection attempts.');
    }

    private function prepareCached(string $sql): PDOStatement
    {
        if (isset($this->statementCache[$sql])) {
            $stmt = $this->statementCache[$sql];
            unset($this->statementCache[$sql]);
            $this->statementCache[$sql] = $stmt;

            return $stmt;
        }

        $stmt = $this->pdo->prepare($sql);
        if (count($this->statementCache) >= self::MAX_CACHED_STATEMENTS) {
            unset($this->statementCache[array_key_first($this->statementCache)]);
        }
        $this->statementCache[$sql] = $stmt;

        return $stmt;
    }

    private static function isConnectionLost(PDOException $e): bool
    {
        $sqlState = (string) $e->getCode();
        if (in_array($sqlState, ['08000', '08003', '08006', '08S01', '57P01', '57P02', '57P03'], true)) {
            return true;
        }
        $msg = strtolower($e->getMessage());
        foreach ([
            'server has gone away',
            'lost connection',
            'no connection to the server',
            'terminating connection',
            'connection reset',
            'broken pipe',
            'server closed the connection',
            'ssl connection has been closed unexpectedly',
        ] as $needle) {
            if (str_contains($msg, $needle)) {
                return true;
            }
        }

        return false;
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
        if (0 === $this->transactionDepth && !$this->pdo->inTransaction()) {
            if (!$this->pdo->beginTransaction()) {
                throw new DatabaseException('Unable to begin database transaction.');
            }
            $this->transactionDepth = 1;

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
            } finally {
                $this->transactionDepth = 0;
            }
        }

        ++$this->transactionDepth;
        $savepoint = 'kongono_sp_' . $this->transactionDepth;
        $this->pdo->exec('SAVEPOINT ' . $savepoint);

        try {
            $result = $callback($this);
            $this->pdo->exec('RELEASE SAVEPOINT ' . $savepoint);

            return $result;
        } catch (Throwable $failure) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->exec('ROLLBACK TO SAVEPOINT ' . $savepoint);
            }
            throw $failure;
        } finally {
            $this->transactionDepth = max(0, $this->transactionDepth - 1);
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
