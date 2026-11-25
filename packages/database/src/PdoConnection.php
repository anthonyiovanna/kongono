<?php

declare(strict_types=1);

namespace Kongono\Database;

use Closure;
use Kongono\Core\Capabilities\ExecutionCapabilities;
use Kongono\Core\Capabilities\QueryGuardableInterface;
use PDO;
use PDOException;
use PDOStatement;
use Throwable;

/**
 * Strict PDO connection supporting PostgreSQL, SQLite, and MySQL with real prepared statements,
 * bounded LRU prepared-statement reuse, and automatic transparent reconnect on idle transport loss
 * in persistent workers (FrankenPHP / RoadRunner).
 */
final class PdoConnection implements ConnectionInterface, QueryGuardableInterface
{
    private const int MAX_CACHED_STATEMENTS = 256;

    /** @var array<int, Closure(string, float): void> */
    private array $queryListeners = [];

    private int $nextListenerId = 0;

    /** @var array<int, Closure(string): void> */
    private array $beforeQueryGuards = [];

    private int $nextGuardId = 0;

    /** @var array<string, PDOStatement> */
    private array $statementCache = [];

    private int $transactionDepth = 0;

    private bool $needsReconnect = false;

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
        $this->needsReconnect = false;
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

    /**
     * Register a scoped pre-execution SQL guard invoked BEFORE any statement reaches PDO.
     *
     * @param Closure(string): void $guard
     * @return Closure(): void
     */
    public function guardBeforeQuery(Closure $guard): Closure
    {
        $id = ++$this->nextGuardId;
        $this->beforeQueryGuards[$id] = $guard;

        return function () use ($id): void {
            unset($this->beforeQueryGuards[$id]);
        };
    }

    private function assertBeforeQuery(string $sql): void
    {
        foreach ($this->beforeQueryGuards as $guard) {
            $guard($sql);
        }
    }

    public static function pgsql(
        string $host,
        int $port,
        string $database,
        string $username,
        #[\SensitiveParameter]
        string $password,
        ?string $sslMode = null,
        ?string $sslRootCert = null,
        ?string $sslCert = null,
        ?string $sslKey = null,
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
        foreach (['sslrootcert' => $sslRootCert, 'sslcert' => $sslCert, 'sslkey' => $sslKey] as $optName => $optVal) {
            if (null !== $optVal && '' !== trim($optVal)) {
                self::assertSafeDsnPart($optVal, $optName);
                $dsn .= ';' . $optName . '=' . $optVal;
            }
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
        if (str_contains($url, "\0")) {
            throw new DatabaseException('Unsafe DATABASE_URL containing NUL byte rejected.');
        }
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
                if ('/:memory:' === $path) {
                    $path = ':memory:';
                } elseif (1 === preg_match('/\A\/[A-Za-z]:\//', $path)) {
                    $path = substr($path, 1);
                }
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
            $sslRootCert = is_string($query['sslrootcert'] ?? null) ? $query['sslrootcert'] : null;
            $sslCert = is_string($query['sslcert'] ?? null) ? $query['sslcert'] : null;
            $sslKey = is_string($query['sslkey'] ?? null) ? $query['sslkey'] : null;

            return self::pgsql($host, $parts['port'] ?? 5432, $dbName, $user, $pass, $sslMode, $sslRootCert, $sslCert, $sslKey);
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
            $sslCaAttr = defined('Pdo\\Mysql::ATTR_SSL_CA')
                ? \Pdo\Mysql::ATTR_SSL_CA
                : (defined('PDO::MYSQL_ATTR_SSL_CA') ? constant('PDO::MYSQL_ATTR_SSL_CA') : null);
            $sslVerifyAttr = defined('Pdo\\Mysql::ATTR_SSL_VERIFY_SERVER_CERT')
                ? \Pdo\Mysql::ATTR_SSL_VERIFY_SERVER_CERT
                : (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT') ? constant('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT') : null);
            $sslCipherAttr = defined('Pdo\\Mysql::ATTR_SSL_CIPHER')
                ? \Pdo\Mysql::ATTR_SSL_CIPHER
                : (defined('PDO::MYSQL_ATTR_SSL_CIPHER') ? constant('PDO::MYSQL_ATTR_SSL_CIPHER') : null);

            $mode = null !== $sslModeRaw && '' !== trim($sslModeRaw) ? strtoupper(trim($sslModeRaw)) : null;
            if (null !== $mode && !in_array($mode, ['DISABLED', 'PREFERRED', 'REQUIRED', 'VERIFY_CA', 'VERIFY_IDENTITY'], true)) {
                throw new DatabaseException(sprintf('Unsupported MySQL ssl-mode "%s".', $sslModeRaw));
            }
            if ('DISABLED' !== $mode && null !== $sslCa && '' !== trim($sslCa)) {
                if (str_contains($sslCa, "\0")) {
                    throw new DatabaseException('Unsafe MySQL SSL CA path.');
                }
                if (is_int($sslCaAttr)) {
                    $options[$sslCaAttr] = $sslCa;
                }
            }
            if (null !== $mode && in_array($mode, ['REQUIRED', 'VERIFY_CA', 'VERIFY_IDENTITY'], true)) {
                if (is_int($sslVerifyAttr)) {
                    $options[$sslVerifyAttr] = 'REQUIRED' !== $mode;
                }
                // Ensure mysqlnd actually negotiates TLS even when ssl-mode=REQUIRED is used without a custom CA file
                if ('REQUIRED' === $mode && is_int($sslCipherAttr) && (!is_int($sslCaAttr) || !isset($options[$sslCaAttr]))) {
                    $options[$sslCipherAttr] = 'DEFAULT';
                }
                if ((!is_int($sslCaAttr) || !isset($options[$sslCaAttr])) && 'REQUIRED' !== $mode) {
                    throw new DatabaseException(sprintf('MySQL ssl-mode=%s requires sslca/sslrootcert in DATABASE_URL.', $mode));
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

    public function inTransaction(): bool
    {
        return $this->transactionDepth > 0 || $this->pdo->inTransaction();
    }

    public function query(string $sql, array $bindings = []): array
    {
        $this->assertBeforeQuery($sql);
        $start = [] !== $this->queryListeners ? hrtime(true) : 0;

        /** @var list<array<string, mixed>> $rows */
        $rows = $this->runWithReconnect(
            $sql,
            $bindings,
            static fn(PDOStatement $stmt): array => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'Database query failed: ',
            allowRetry: !ExecutionCapabilities::isWriteSql($sql),
        );
        $this->notifyQuery($sql, $start);

        return $rows;
    }

    public function first(string $sql, array $bindings = []): ?array
    {
        $this->assertBeforeQuery($sql);
        $start = [] !== $this->queryListeners ? hrtime(true) : 0;

        $row = $this->runWithReconnect(
            $sql,
            $bindings,
            static fn(PDOStatement $stmt): mixed => $stmt->fetch(PDO::FETCH_ASSOC),
            'Database query failed: ',
            allowRetry: !ExecutionCapabilities::isWriteSql($sql),
        );
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
        $this->assertBeforeQuery($sql);
        $start = [] !== $this->queryListeners ? hrtime(true) : 0;

        $count = $this->runWithReconnect(
            $sql,
            $bindings,
            static fn(PDOStatement $stmt): int => $stmt->rowCount(),
            'Database execution failed: ',
            allowRetry: false,
        );
        $this->notifyQuery($sql, $start);

        return $count;
    }

    /**
     * @template T
     * @param list<mixed>                 $bindings
     * @param Closure(PDOStatement): T    $extractor
     * @return T
     */
    private function runWithReconnect(
        string $sql,
        array $bindings,
        Closure $extractor,
        string $errorPrefix,
        bool $allowRetry = false,
    ): mixed {
        if ($this->needsReconnect && null !== $this->connector && !$this->inTransaction()) {
            $this->reconnect();
        }

        $canRetry = $allowRetry && null !== $this->connector && !$this->inTransaction();
        $attempt = 0;

        while (true) {
            ++$attempt;
            try {
                $stmt = $this->prepareCached($sql);
                if (PDOStatement::class === $stmt::class) {
                    self::bindTypedValues($stmt, $bindings);
                    $stmt->execute();
                } else {
                    $stmt->execute($bindings);
                }
                $result = $extractor($stmt);
                if (PDOStatement::class === $stmt::class) {
                    $stmt->closeCursor();
                }

                return $result;
            } catch (PDOException $e) {
                unset($this->statementCache[$sql]);
                if (self::isConnectionLost($e)) {
                    if (1 === $attempt && $canRetry) {
                        $this->reconnect();
                        continue;
                    }
                    // Mark connection dirty so next top-level operation reconnects lazily without replaying failed writes
                    $this->statementCache = [];
                    $this->needsReconnect = true;
                }

                throw new DatabaseException($errorPrefix . $e->getMessage(), previous: $e);
            }
        }
    }

    /**
     * Bind positional parameters with native PDO types so native prepared statements (`ATTR_EMULATE_PREPARES => false`)
     * handle booleans (`false` vs `""`), integers (`LIMIT ?`), and NULLs accurately across Postgres, MySQL, and SQLite.
     *
     * @param list<mixed> $bindings
     */
    private static function bindTypedValues(PDOStatement $stmt, array $bindings): void
    {
        foreach ($bindings as $idx => $value) {
            $pos = $idx + 1;
            if (null === $value) {
                $stmt->bindValue($pos, null, PDO::PARAM_NULL);
            } elseif (is_bool($value)) {
                $stmt->bindValue($pos, $value, PDO::PARAM_BOOL);
            } elseif (is_int($value)) {
                $stmt->bindValue($pos, $value, PDO::PARAM_INT);
            } elseif (is_scalar($value)) {
                $stmt->bindValue($pos, (string) $value, PDO::PARAM_STR);
            } else {
                $stmt->bindValue($pos, $value, PDO::PARAM_STR);
            }
        }
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
            if ($this->needsReconnect && null !== $this->connector) {
                $this->reconnect();
            }
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
                try {
                    if ($this->pdo->inTransaction()) {
                        $this->pdo->rollBack();
                    }
                } catch (Throwable) {
                    $this->needsReconnect = true;
                }
                throw $failure;
            } finally {
                $this->transactionDepth = 0;
            }
        }

        $nextDepth = $this->transactionDepth + 1;
        $savepoint = 'kongono_sp_' . $nextDepth;
        $this->pdo->exec('SAVEPOINT ' . $savepoint);
        $this->transactionDepth = $nextDepth;

        try {
            $result = $callback($this);
            $this->pdo->exec('RELEASE SAVEPOINT ' . $savepoint);

            return $result;
        } catch (Throwable $failure) {
            try {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->exec('ROLLBACK TO SAVEPOINT ' . $savepoint);
                }
            } catch (Throwable) {
                // Preserve original $failure even if the connection dropped before SAVEPOINT rollback
                $this->needsReconnect = true;
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
