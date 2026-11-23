<?php

declare(strict_types=1);

namespace Kongono\Cli\Context;

use Kongono\Core\CoreException;
use Kongono\Core\Diagnostics\SecretScrubber;
use Kongono\Core\Graph\ApplicationGraph;
use Kongono\Database\ConnectionInterface;
use Kongono\Database\Dialect;
use Kongono\Http\App;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Throwable;

/**
 * Safe, secret-scrubbed view of a Kongono application directory and its live ApplicationGraph.
 * Feeds accurate project context to Oshie, Chie, and Hantei.
 */
final readonly class ProjectContext
{
    /** @var list<string> */
    private const array FORBIDDEN_SECRET_FILES = [
        'id_rsa',
        'id_ed25519',
        'id_ecdsa',
        '.netrc',
        '.npmrc',
        '.pypirc',
        '.htpasswd',
        '.pgpass',
        'auth.json',
        'credentials.json',
        'service-account.json',
    ];

    public string $rootPath;

    public function __construct(
        string $rootPath,
        public ?ApplicationGraph $graph = null,
        public ?App $app = null,
    ) {
        $resolved = realpath($rootPath);
        if (false === $resolved || !is_dir($resolved)) {
            throw new CoreException(sprintf('Project root directory does not exist: "%s".', $rootPath));
        }
        $this->rootPath = $resolved;
    }

    /** Load a Kongono app from app/bootstrap.php (if present) and capture its live ApplicationGraph & App instance. */
    public static function discover(string $rootPath): self
    {
        $resolved = realpath($rootPath);
        if (false === $resolved || !is_dir($resolved)) {
            throw new CoreException(sprintf('Project root directory does not exist: "%s".', $rootPath));
        }

        $graph = null;
        $loadedApp = null;
        $bootstrap = $resolved . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'bootstrap.php';
        if (is_file($bootstrap)) {
            try {
                $app = require $bootstrap;
                if ($app instanceof App) {
                    $loadedApp = $app;
                    $graph = $app->graph();
                }
            } catch (Throwable) {
                // Gracefully degrade when inspecting a newly scaffolded or broken app
                $loadedApp = null;
                $graph = null;
            }
        }

        return new self($resolved, $graph, $loadedApp);
    }

    /**
     * List relative paths of project source/template/config files (excluding vendor/var/.git).
     *
     * @return list<string>
     */
    public function listFiles(int $limit = 100): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->rootPath));

        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo || !$file->isFile()) {
                continue;
            }
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($this->rootPath) + 1));
            if (
                str_starts_with($relative, 'vendor/')
                || str_starts_with($relative, '.git/')
                || str_starts_with($relative, 'var/')
                || str_starts_with($relative, '.cache/')
                || str_starts_with($relative, '.phpunit.cache/')
            ) {
                continue;
            }
            $files[] = $relative;
            if (count($files) >= $limit) {
                break;
            }
        }

        sort($files, SORT_STRING);

        return $files;
    }

    /**
     * Read one contained file safely, blocking sensitive secret/credential files post-realpath
     * and scrubbing common secret patterns before sharing with an AI.
     *
     * @param int<1, max> $maxBytes
     */
    public function readFileSafe(string $relativePath, int $maxBytes = 32768): string
    {
        // Pre-check requested path before touching filesystem to avoid file-existence oracles on secrets
        $requestedRelative = ltrim(str_replace('\\', '/', trim($relativePath)), '/');
        if ('' !== $requestedRelative) {
            $this->assertReadablePath($requestedRelative);
        }

        $fullPath = $this->resolveContainedPath($relativePath, mustExist: true);
        $canonicalRelative = str_replace('\\', '/', substr($fullPath, strlen($this->rootPath) + 1));
        $this->assertReadablePath($canonicalRelative);

        $raw = file_get_contents($fullPath, length: $maxBytes);
        if (false === $raw) {
            throw new CoreException(sprintf('Unable to read "%s".', $relativePath));
        }

        return SecretScrubber::scrub($raw);
    }

    /**
     * Read a bounded numbered line range (`startLine..endLine`, max 300 lines) from a contained project file.
     * Enforces identical secret-file blocking and SecretScrubber protection as `readFileSafe()`.
     */
    public function readFileLines(string $relativePath, int $startLine = 1, int $endLine = 200): string
    {
        $contents = $this->readFileSafe($relativePath, 131072);
        $lines = preg_split("/\r\n|\n|\r/", $contents);
        if (false === $lines) {
            return '';
        }

        $total = count($lines);
        $from = max(1, min($startLine, max(1, $total)));
        $to = max($from, min($endLine, $from + 299, $total));

        $out = [];
        for ($i = $from; $i <= $to; ++$i) {
            $out[] = sprintf('%d: %s', $i, $lines[$i - 1] ?? '');
        }

        return implode("\n", $out);
    }

    /**
     * Bounded, case-insensitive literal substring search across readable project files.
     * Skips `vendor/`, `.git/`, `var/`, symlinks, binary files, and every forbidden secret file.
     *
     * @return list<array{file: string, line: int, text: string}>
     */
    public function searchCode(string $query, ?string $directory = null, int $maxMatches = 40): array
    {
        $needle = trim($query);
        if ('' === $needle) {
            return [];
        }

        $limit = max(1, min($maxMatches, 100));
        $dirPrefix = null;
        if (null !== $directory && '' !== trim($directory)) {
            $cleanDir = trim(str_replace('\\', '/', trim($directory)), '/');
            if (str_contains($cleanDir, '..') || str_contains($cleanDir, "\0")) {
                throw new CoreException(sprintf('Search directory "%s" is invalid or attempts directory traversal.', $directory));
            }
            $dirPrefix = $cleanDir . '/';
        }

        /** @var list<array{file: string, line: int, text: string}> $matches */
        $matches = [];
        foreach ($this->listFiles(400) as $relative) {
            if (null !== $dirPrefix && !str_starts_with($relative, $dirPrefix) && $relative !== rtrim($dirPrefix, '/')) {
                continue;
            }
            try {
                $this->assertReadablePath($relative);
                $fullPath = $this->resolveContainedPath($relative, mustExist: true);
            } catch (CoreException) {
                continue;
            }

            $size = @filesize($fullPath);
            if (false === $size || $size > 262_144) {
                continue;
            }
            $raw = @file_get_contents($fullPath);
            if (false === $raw || str_contains($raw, "\0")) {
                continue;
            }
            if (false === stripos($raw, $needle)) {
                continue;
            }

            $lines = preg_split("/\r\n|\n|\r/", $raw);
            if (false === $lines) {
                continue;
            }
            foreach ($lines as $idx => $lineText) {
                if (false !== stripos($lineText, $needle)) {
                    $matches[] = [
                        'file' => $relative,
                        'line' => $idx + 1,
                        'text' => substr(SecretScrubber::scrub(trim($lineText)), 0, 240),
                    ];
                    if (count($matches) >= $limit) {
                        return $matches;
                    }
                }
            }
        }

        return $matches;
    }

    /**
     * Inspect live database schema metadata (tables, columns, types, nullability, indexes) strictly
     * without reading any row data. Supports SQLite, PostgreSQL, and MySQL.
     *
     * @return array{
     *     dialect: ?string,
     *     tables: array<string, array{
     *         columns: list<array{name: string, type: string, nullable: bool, default: ?string, primary: bool}>,
     *         indexes: list<string>
     *     }>,
     *     note?: string
     * }
     */
    public function inspectSchema(): array
    {
        if (null === $this->app || !$this->app->container()->has(ConnectionInterface::class)) {
            return [
                'dialect' => null,
                'tables' => [],
                'note' => 'No Kongono\\Database\\ConnectionInterface registered in app container.',
            ];
        }

        try {
            $db = $this->app->container()->make(ConnectionInterface::class);
            $dialect = $db->dialect();

            if (Dialect::Sqlite === $dialect) {
                return $this->inspectSqliteSchema($db);
            }
            if (Dialect::Postgres === $dialect) {
                return $this->inspectPostgresSchema($db);
            }

            return $this->inspectMysqlSchema($db);
        } catch (Throwable $e) {
            return [
                'dialect' => null,
                'tables' => [],
                'note' => 'Schema inspection unavailable: ' . SecretScrubber::scrub($e->getMessage()),
            ];
        }
    }

    /**
     * @return array{
     *     dialect: string,
     *     tables: array<string, array{
     *         columns: list<array{name: string, type: string, nullable: bool, default: ?string, primary: bool}>,
     *         indexes: list<string>
     *     }>
     * }
     */
    private function inspectSqliteSchema(ConnectionInterface $db): array
    {
        $tableRows = $db->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
        $tables = [];

        foreach ($tableRows as $row) {
            $tableName = is_string($row['name'] ?? null) ? $row['name'] : '';
            if (1 !== preg_match('/\A[a-zA-Z_][a-zA-Z0-9_]*\z/', $tableName)) {
                continue;
            }
            $quoted = '"' . $tableName . '"';
            $colRows = $db->query('PRAGMA table_info(' . $quoted . ')');
            $columns = [];
            foreach ($colRows as $col) {
                $colName = is_string($col['name'] ?? null) ? $col['name'] : '';
                if ('' === $colName) {
                    continue;
                }
                $defaultVal = $col['dflt_value'] ?? null;
                $columns[] = [
                    'name' => $colName,
                    'type' => is_string($col['type'] ?? null) ? $col['type'] : 'TEXT',
                    'nullable' => 0 === (int) (is_scalar($col['notnull'] ?? null) ? $col['notnull'] : 0),
                    'default' => is_scalar($defaultVal) ? (string) $defaultVal : null,
                    'primary' => 0 !== (int) (is_scalar($col['pk'] ?? null) ? $col['pk'] : 0),
                ];
            }

            $idxRows = $db->query('PRAGMA index_list(' . $quoted . ')');
            $indexes = [];
            foreach ($idxRows as $idx) {
                $idxName = is_string($idx['name'] ?? null) ? $idx['name'] : '';
                if ('' !== $idxName) {
                    $indexes[] = $idxName;
                }
            }

            $tables[$tableName] = ['columns' => $columns, 'indexes' => $indexes];
        }

        return ['dialect' => Dialect::Sqlite->value, 'tables' => $tables];
    }

    /**
     * @return array{
     *     dialect: string,
     *     tables: array<string, array{
     *         columns: list<array{name: string, type: string, nullable: bool, default: ?string, primary: bool}>,
     *         indexes: list<string>
     *     }>
     * }
     */
    private function inspectPostgresSchema(ConnectionInterface $db): array
    {
        $rows = $db->query(
            "SELECT table_name, column_name, data_type, is_nullable, column_default
             FROM information_schema.columns
             WHERE table_schema = 'public'
             ORDER BY table_name, ordinal_position",
        );
        $tables = [];
        foreach ($rows as $row) {
            $tableName = is_string($row['table_name'] ?? null) ? $row['table_name'] : '';
            $colName = is_string($row['column_name'] ?? null) ? $row['column_name'] : '';
            if ('' === $tableName || '' === $colName) {
                continue;
            }
            $tables[$tableName] ??= ['columns' => [], 'indexes' => []];
            $defaultVal = $row['column_default'] ?? null;
            $tables[$tableName]['columns'][] = [
                'name' => $colName,
                'type' => is_string($row['data_type'] ?? null) ? $row['data_type'] : 'text',
                'nullable' => 'YES' === strtoupper(is_string($row['is_nullable'] ?? null) ? $row['is_nullable'] : 'YES'),
                'default' => is_scalar($defaultVal) ? (string) $defaultVal : null,
                'primary' => 'id' === $colName,
            ];
        }

        $idxRows = $db->query("SELECT tablename, indexname FROM pg_indexes WHERE schemaname = 'public' ORDER BY tablename, indexname");
        foreach ($idxRows as $idx) {
            $tableName = is_string($idx['tablename'] ?? null) ? $idx['tablename'] : '';
            $idxName = is_string($idx['indexname'] ?? null) ? $idx['indexname'] : '';
            if (isset($tables[$tableName]) && '' !== $idxName) {
                $tables[$tableName]['indexes'][] = $idxName;
            }
        }

        return ['dialect' => Dialect::Postgres->value, 'tables' => $tables];
    }

    /**
     * @return array{
     *     dialect: string,
     *     tables: array<string, array{
     *         columns: list<array{name: string, type: string, nullable: bool, default: ?string, primary: bool}>,
     *         indexes: list<string>
     *     }>
     * }
     */
    private function inspectMysqlSchema(ConnectionInterface $db): array
    {
        $rows = $db->query(
            'SELECT TABLE_NAME AS table_name, COLUMN_NAME AS column_name, DATA_TYPE AS data_type,
                    IS_NULLABLE AS is_nullable, COLUMN_DEFAULT AS column_default, COLUMN_KEY AS column_key
             FROM information_schema.columns
             WHERE table_schema = DATABASE()
             ORDER BY TABLE_NAME, ORDINAL_POSITION',
        );
        $tables = [];
        foreach ($rows as $row) {
            $tableName = is_string($row['table_name'] ?? null) ? $row['table_name'] : '';
            $colName = is_string($row['column_name'] ?? null) ? $row['column_name'] : '';
            if ('' === $tableName || '' === $colName) {
                continue;
            }
            $tables[$tableName] ??= ['columns' => [], 'indexes' => []];
            $defaultVal = $row['column_default'] ?? null;
            $tables[$tableName]['columns'][] = [
                'name' => $colName,
                'type' => is_string($row['data_type'] ?? null) ? $row['data_type'] : 'varchar',
                'nullable' => 'YES' === strtoupper(is_string($row['is_nullable'] ?? null) ? $row['is_nullable'] : 'YES'),
                'default' => is_scalar($defaultVal) ? (string) $defaultVal : null,
                'primary' => 'PRI' === strtoupper(is_string($row['column_key'] ?? null) ? $row['column_key'] : ''),
            ];
        }

        return ['dialect' => Dialect::Mysql->value, 'tables' => $tables];
    }

    /** Write a file strictly within the project root (rejects path traversal, symlinks, and sensitive write targets). */
    public function writeFileContained(string $relativePath, string $content): string
    {
        $fullPath = $this->resolveContainedPath($relativePath, mustExist: false);
        $canonicalRelative = str_replace('\\', '/', substr($fullPath, strlen($this->rootPath) + 1));
        $this->assertWritablePath($canonicalRelative);

        $dir = dirname($fullPath);
        if (!is_dir($dir) && !@mkdir($dir, 0o775, true) && !is_dir($dir)) {
            throw new CoreException(sprintf('Unable to create directory for "%s".', $relativePath));
        }
        if (is_link($fullPath)) {
            throw new CoreException(sprintf('Refusing to write through symbolic link "%s".', $relativePath));
        }
        if (false === file_put_contents($fullPath, $content, LOCK_EX)) {
            throw new CoreException(sprintf('Unable to write file "%s".', $relativePath));
        }

        return $fullPath;
    }

    /** Build a concise, secret-scrubbed summary prompt block describing the current Kongono project. */
    public function promptSummary(): string
    {
        $lines = [
            'Project root: ' . basename($this->rootPath),
            'Files: ' . implode(', ', $this->listFiles(60)),
        ];

        if (null !== $this->graph) {
            $lines[] = 'Live ApplicationGraph JSON:';
            $lines[] = $this->graph->toJson(pretty: false);
        }

        return SecretScrubber::scrub(implode("\n", $lines));
    }

    private function resolveContainedPath(string $relativePath, bool $mustExist): string
    {
        $normalized = ltrim(str_replace('\\', '/', trim($relativePath)), '/');
        if ('' === $normalized || str_contains($normalized, '..') || str_contains($normalized, "\0")) {
            throw new CoreException(sprintf('Path "%s" is invalid or attempts directory traversal.', $relativePath));
        }

        // Reject symlinks on EVERY intermediate segment AND on the target leaf itself (blocks both read & write escapes)
        $walk = $this->rootPath;
        foreach (explode('/', $normalized) as $segment) {
            if ('' === $segment || '.' === $segment) {
                throw new CoreException(sprintf('Path "%s" contains invalid segment.', $relativePath));
            }
            $walk .= DIRECTORY_SEPARATOR . $segment;
            if (is_link($walk)) {
                throw new CoreException(sprintf('Symlink "%s" is not permitted.', $relativePath));
            }
        }

        $candidate = $this->rootPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalized);
        $prefix = rtrim($this->rootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if ($mustExist) {
            $real = realpath($candidate);
            if (false === $real || !is_file($real) || !str_starts_with($real, $prefix)) {
                throw new CoreException(sprintf('File "%s" does not exist within project root.', $relativePath));
            }

            return $real;
        }

        // Verify nearest existing ancestor directory is strictly contained inside rootPath
        $probeDir = dirname($candidate);
        while (!file_exists($probeDir) && $probeDir !== dirname($probeDir)) {
            $probeDir = dirname($probeDir);
        }
        $realDir = realpath($probeDir);
        if (false === $realDir || ($realDir !== $this->rootPath && !str_starts_with($realDir . DIRECTORY_SEPARATOR, $prefix))) {
            throw new CoreException(sprintf('Target directory for "%s" escapes project root.', $relativePath));
        }

        return $candidate;
    }

    private function assertReadablePath(string $canonicalRelative): void
    {
        $lower = strtolower($canonicalRelative);
        $base = strtolower(basename($canonicalRelative));

        $forbiddenExt = (bool) preg_match('/\.(pem|key|p12|pfx|jks|kdbx|ppk|pk8|db|sqlite3?(?:-(?:wal|shm))?)\z/', $base);
        $forbiddenName = (str_starts_with($base, '.env') && '.env.example' !== $base)
            || in_array($base, self::FORBIDDEN_SECRET_FILES, true)
            || str_starts_with($lower, '.git/')
            || str_contains($lower, '/.git/')
            || str_starts_with($lower, '.ssh/')
            || str_contains($lower, '/.ssh/')
            || '.aws/credentials' === $lower
            || str_contains($lower, '/.aws/credentials')
            || str_starts_with($lower, 'var/cache/')
            || str_starts_with($lower, 'var/storage/')
            || '.docker/config.json' === $lower
            || str_contains($lower, '/.docker/config.json');

        if ($forbiddenExt || $forbiddenName) {
            throw new CoreException(sprintf('Reading sensitive secret/credential file "%s" is forbidden.', $canonicalRelative));
        }
    }

    private function assertWritablePath(string $canonicalRelative): void
    {
        $lower = strtolower($canonicalRelative);
        $base = strtolower(basename($canonicalRelative));

        $forbiddenExt = (bool) preg_match('/\.(pem|key|p12|pfx|ppk|pk8)\z/', $base);
        $forbiddenTarget = (str_starts_with($base, '.env') && '.env.example' !== $base)
            || str_starts_with($lower, '.git/')
            || str_contains($lower, '/.git/')
            || str_starts_with($lower, '.github/')
            || str_contains($lower, '/.github/')
            || str_starts_with($lower, '.vscode/')
            || str_starts_with($lower, 'vendor/')
            || str_starts_with($lower, 'deploy/')
            || str_starts_with($lower, 'bin/')
            || in_array($lower, ['composer.json', 'composer.lock', '.gitlab-ci.yml', 'dockerfile', 'caddyfile', '.htaccess'], true)
            || str_starts_with($base, 'docker-compose')
            || str_starts_with($base, '.php-cs-fixer')
            || str_starts_with($base, 'phpunit.xml')
            || str_starts_with($base, 'phpstan.neon')
            || in_array($base, self::FORBIDDEN_SECRET_FILES, true);

        if ($forbiddenExt || $forbiddenTarget) {
            throw new CoreException(sprintf('Writing to protected sensitive path "%s" is forbidden.', $canonicalRelative));
        }
    }
}
