<?php

declare(strict_types=1);

namespace Kongono\Cli\Context;

use FilesystemIterator;
use Kongono\Core\CoreException;
use Kongono\Core\Diagnostics\SecretScrubber;
use Kongono\Core\Graph\ApplicationGraph;
use Kongono\Database\ConnectionInterface;
use Kongono\Database\Dialect;
use Kongono\Http\App;
use RecursiveCallbackFilterIterator;
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
        'id_dsa',
        'id_ed25519',
        'id_ecdsa',
        'id_ecdsa_sk',
        'id_ed25519_sk',
        '.netrc',
        '.npmrc',
        '.pypirc',
        '.htpasswd',
        '.pgpass',
        'auth.json',
        'credentials.json',
        'secrets.json',
        'service-account.json',
    ];

    /** @var list<string> */
    private const array IGNORED_DIRS = [
        'vendor',
        '.git',
        'var',
        '.cache',
        '.phpunit.cache',
        'node_modules',
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

    /** Returns true when safe/static-only mode is enabled (`KONGONO_SAFE_MODE=1` or `KONGONO_NO_BOOTSTRAP=1`). */
    public static function isSafeModeEnabled(): bool
    {
        foreach (['KONGONO_SAFE_MODE', 'KONGONO_NO_BOOTSTRAP'] as $var) {
            $val = getenv($var);
            if (false !== $val && in_array(strtolower(trim($val)), ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Load a Kongono app from app/bootstrap.php (if present and bootstrap execution is permitted)
     * and capture its live ApplicationGraph & App instance.
     *
     * Pass `allowBootstrap: false` (or set `KONGONO_SAFE_MODE=1`) when inspecting untrusted repositories
     * so read-only CLI/MCP tools never execute arbitrary `app/bootstrap.php` code.
     */
    public static function discover(string $rootPath, ?bool $allowBootstrap = null): self
    {
        $resolved = realpath($rootPath);
        if (false === $resolved || !is_dir($resolved)) {
            throw new CoreException(sprintf('Project root directory does not exist: "%s".', $rootPath));
        }

        $graph = null;
        $loadedApp = null;
        $bootstrapAllowed = $allowBootstrap ?? !self::isSafeModeEnabled();
        $appDir = $resolved . DIRECTORY_SEPARATOR . 'app';
        $bootstrap = $appDir . DIRECTORY_SEPARATOR . 'bootstrap.php';
        if ($bootstrapAllowed && !is_link($appDir) && !is_link($bootstrap) && is_file($bootstrap)) {
            $realBootstrap = realpath($bootstrap);
            $prefix = rtrim($resolved, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            if (false !== $realBootstrap && str_starts_with($realBootstrap, $prefix)) {
                try {
                    $app = require $realBootstrap;
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
        }

        return new self($resolved, $graph, $loadedApp);
    }

    /**
     * List relative paths of project source/template/config files (excluding vendor/var/.git, symlinks, and secret files).
     *
     * @return list<string>
     */
    public function listFiles(int $limit = 100, ?string $directory = null): array
    {
        $scanRoot = $this->rootPath;
        if (null !== $directory && '' !== trim($directory)) {
            $cleanDir = trim(str_replace('\\', '/', trim($directory)), '/');
            if ('' !== $cleanDir) {
                if (str_contains($cleanDir, '..') || str_contains($cleanDir, "\0") || str_contains($cleanDir, ':')) {
                    throw new CoreException(sprintf('Directory "%s" is invalid or attempts directory traversal.', $directory));
                }
                $walk = $this->rootPath;
                foreach (explode('/', $cleanDir) as $segment) {
                    if ('' === $segment || '.' === $segment) {
                        throw new CoreException(sprintf('Directory "%s" contains invalid segment.', $directory));
                    }
                    $walk .= DIRECTORY_SEPARATOR . $segment;
                    if (is_link($walk)) {
                        throw new CoreException(sprintf('Symlink directory "%s" is not permitted.', $directory));
                    }
                }
                $realScan = realpath($walk);
                $prefix = rtrim($this->rootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
                if (false === $realScan || !is_dir($realScan) || ($realScan !== $this->rootPath && !str_starts_with($realScan . DIRECTORY_SEPARATOR, $prefix))) {
                    return [];
                }
                $scanRoot = $realScan;
            }
        }

        $files = [];
        $dirIt = new RecursiveDirectoryIterator($scanRoot, FilesystemIterator::SKIP_DOTS);
        $filter = new RecursiveCallbackFilterIterator(
            $dirIt,
            static function (SplFileInfo $current): bool {
                if ($current->isLink()) {
                    return false;
                }
                if ($current->isDir()) {
                    return !in_array(strtolower($current->getFilename()), self::IGNORED_DIRS, true);
                }

                return true;
            },
        );
        $iterator = new RecursiveIteratorIterator($filter);
        $max = max(1, $limit);

        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo || $file->isLink() || !$file->isFile()) {
                continue;
            }
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($this->rootPath) + 1));
            if (!$this->isReadablePath($relative)) {
                continue;
            }
            $files[] = $relative;
            if (count($files) >= $max) {
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

        $handle = $this->openVerifiedContainedFile($fullPath, $relativePath);
        try {
            $raw = fread($handle, $maxBytes);
            if (false === $raw) {
                throw new CoreException(sprintf('Unable to read "%s".', $relativePath));
            }
        } finally {
            fclose($handle);
        }

        return SecretScrubber::scrub($raw);
    }

    /**
     * Read up to the final `$maxBytes` (or newly appended bytes from `$fromOffset`) of a contained project file,
     * enforcing the same secret-file blocking and SecretScrubber protection as `readFileSafe()`.
     *
     * @param int<1, max> $maxBytes
     * @return array{content: string, size: int, offset: int}
     */
    public function readFileTailSafe(string $relativePath, int $maxBytes = 131072, ?int $fromOffset = null): array
    {
        $requestedRelative = ltrim(str_replace('\\', '/', trim($relativePath)), '/');
        if ('' !== $requestedRelative) {
            $this->assertReadablePath($requestedRelative);
        }

        $fullPath = $this->resolveContainedPath($relativePath, mustExist: true);
        $canonicalRelative = str_replace('\\', '/', substr($fullPath, strlen($this->rootPath) + 1));
        $this->assertReadablePath($canonicalRelative);

        $handle = $this->openVerifiedContainedFile($fullPath, $relativePath);
        try {
            $stat = fstat($handle);
            if (false === $stat) {
                throw new CoreException(sprintf('Unable to stat "%s".', $relativePath));
            }
            $size = $stat['size'];

            $start = null !== $fromOffset && $fromOffset >= 0 && $fromOffset <= $size
                ? max($fromOffset, $size - $maxBytes)
                : max(0, $size - $maxBytes);
            $toRead = $size - $start;
            if ($toRead <= 0) {
                return ['content' => '', 'size' => $size, 'offset' => $size];
            }

            if ($start > 0 && 0 !== fseek($handle, $start, SEEK_SET)) {
                throw new CoreException(sprintf('Unable to seek "%s".', $relativePath));
            }
            $raw = fread($handle, min($maxBytes, $toRead));
            if (false === $raw) {
                throw new CoreException(sprintf('Unable to read "%s".', $relativePath));
            }
        } finally {
            fclose($handle);
        }

        return [
            'content' => SecretScrubber::scrub($raw),
            'size' => $size,
            'offset' => $start + strlen($raw),
        ];
    }

    /**
     * Open a contained regular file while tightening symlink-replacement TOCTOU windows via pre/post
     * `is_link()`, `realpath()` containment, regular-file mode, and POSIX device/inode verification.
     *
     * @return resource
     */
    private function openVerifiedContainedFile(string $fullPath, string $relativePath)
    {
        clearstatcache(true, $fullPath);
        if (is_link($fullPath)) {
            throw new CoreException(sprintf('Refusing to read symbolic link "%s".', $relativePath));
        }
        $preStat = @lstat($fullPath);
        if (false === $preStat || 0o100000 !== ($preStat['mode'] & 0o170000)) {
            throw new CoreException(sprintf('Refusing to read non-regular file "%s".', $relativePath));
        }

        $handle = @fopen($fullPath, 'rb');
        if (false === $handle) {
            throw new CoreException(sprintf('Unable to read "%s".', $relativePath));
        }

        $postStat = @fstat($handle);
        clearstatcache(true, $fullPath);
        $postReal = realpath($fullPath);
        $prefix = rtrim($this->rootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        $inodeMismatch = false !== $postStat
            && $preStat['ino'] > 0
            && $postStat['ino'] > 0
            && ($preStat['ino'] !== $postStat['ino'] || $preStat['dev'] !== $postStat['dev']);

        if (
            false === $postStat
            || 0o100000 !== ($postStat['mode'] & 0o170000)
            || is_link($fullPath)
            || false === $postReal
            || !str_starts_with($postReal, $prefix)
            || $inodeMismatch
        ) {
            fclose($handle);
            throw new CoreException(sprintf('File "%s" failed containment verification during read.', $relativePath));
        }

        return $handle;
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
        $cleanDir = null;
        if (null !== $directory && '' !== trim($directory)) {
            $cleanDir = trim(str_replace('\\', '/', trim($directory)), '/');
            if (str_contains($cleanDir, '..') || str_contains($cleanDir, "\0")) {
                throw new CoreException(sprintf('Search directory "%s" is invalid or attempts directory traversal.', $directory));
            }
        }

        /** @var list<array{file: string, line: int, text: string}> $matches */
        $matches = [];
        foreach ($this->listFiles(400, $cleanDir) as $relative) {
            try {
                $this->assertReadablePath($relative);
                $fullPath = $this->resolveContainedPath($relative, mustExist: true);
            } catch (CoreException) {
                continue;
            }

            try {
                $handle = $this->openVerifiedContainedFile($fullPath, $relative);
            } catch (CoreException) {
                continue;
            }
            try {
                $stat = fstat($handle);
                if (false === $stat || $stat['size'] > 262_144) {
                    continue;
                }
                $raw = stream_get_contents($handle, 262_145);
            } finally {
                fclose($handle);
            }
            if (!is_string($raw) || strlen($raw) > 262_144 || str_contains($raw, "\0")) {
                continue;
            }
            // Scrub the ENTIRE file before searching so multiline PEM private keys and secrets can never
            // be matched line-by-line or probed as an existence oracle before transmission to an AI provider
            $scrubbedFile = SecretScrubber::scrub($raw);
            if (false === stripos($scrubbedFile, $needle)) {
                continue;
            }

            $lines = preg_split("/\r\n|\n|\r/", $scrubbedFile);
            if (false === $lines) {
                continue;
            }
            foreach ($lines as $idx => $lineText) {
                if (false !== stripos($lineText, $needle)) {
                    $matches[] = [
                        'file' => $relative,
                        'line' => $idx + 1,
                        'text' => substr(trim($lineText), 0, 240),
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

    /**
     * Read raw (unscrubbed) contents of an existing writable project file strictly contained inside the project root,
     * returning null if it does not yet exist. Rejects symlinks on any segment, traversal, and protected/secret paths.
     */
    public function readRawWritableFileIfExists(string $relativePath): ?string
    {
        $fullPath = $this->resolveContainedPath($relativePath, mustExist: false);
        $canonicalRelative = str_replace('\\', '/', substr($fullPath, strlen($this->rootPath) + 1));
        $this->assertWritablePath($canonicalRelative);
        $this->assertReadablePath($canonicalRelative);

        if (!file_exists($fullPath) || is_link($fullPath) || !is_file($fullPath)) {
            return null;
        }
        $real = realpath($fullPath);
        $prefix = rtrim($this->rootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (false === $real || !str_starts_with($real, $prefix)) {
            throw new CoreException(sprintf('File "%s" escapes project root.', $relativePath));
        }

        $raw = file_get_contents($real);

        return false !== $raw ? $raw : null;
    }

    /** Safely delete an existing writable file strictly contained inside the project root (never follows symlinks). */
    public function deleteFileContained(string $relativePath): void
    {
        $fullPath = $this->resolveContainedPath($relativePath, mustExist: false);
        $canonicalRelative = str_replace('\\', '/', substr($fullPath, strlen($this->rootPath) + 1));
        $this->assertWritablePath($canonicalRelative);

        if (!file_exists($fullPath) || is_link($fullPath) || !is_file($fullPath)) {
            return;
        }
        $real = realpath($fullPath);
        $prefix = rtrim($this->rootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (false === $real || !str_starts_with($real, $prefix)) {
            throw new CoreException(sprintf('Refusing to delete "%s": path escapes project root.', $relativePath));
        }

        @unlink($real);
    }

    /**
     * Load optional project-specific AI engineering guidelines from `AGENTS.md`, `.kongono/companions.md`,
     * or `CLAUDE.md` (bounded and secret-scrubbed; intended to be wrapped in PromptGuard UntrustedInput).
     *
     * @param int<256, 32768> $maxBytes
     */
    public function projectInstructions(int $maxBytes = 3500): ?string
    {
        foreach (['.kongono/companions.md', 'AGENTS.md', 'CLAUDE.md'] as $candidate) {
            $full = $this->rootPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $candidate);
            if (!is_file($full) || is_link($full)) {
                continue;
            }
            try {
                $content = trim($this->readFileSafe($candidate, $maxBytes));
                if ('' !== $content) {
                    return $content;
                }
            } catch (CoreException) {
                continue;
            }
        }

        return null;
    }

    /** Build a concise, secret-scrubbed summary prompt block describing the current Kongono project. */
    public function promptSummary(): string
    {
        $lines = [
            'Project root: ' . basename($this->rootPath),
            'Files: ' . implode(', ', $this->listFiles(60)),
        ];

        $instructions = $this->projectInstructions();
        if (null !== $instructions) {
            $lines[] = "Project conventions (AGENTS.md):\n" . $instructions;
        }

        if (null !== $this->graph) {
            $lines[] = 'Live ApplicationGraph JSON:';
            $lines[] = $this->graph->toJson(pretty: false);
        }

        return SecretScrubber::scrub(implode("\n", $lines));
    }

    private function resolveContainedPath(string $relativePath, bool $mustExist): string
    {
        $normalized = ltrim(str_replace('\\', '/', trim($relativePath)), '/');
        if (
            '' === $normalized
            || str_contains($normalized, '..')
            || str_contains($normalized, ':')
            || 1 === preg_match('/[\x00-\x1f\x7f]/', $normalized)
        ) {
            throw new CoreException(sprintf('Path "%s" is invalid or attempts directory traversal.', $relativePath));
        }

        // Reject symlinks, Windows trailing dots/spaces, and reserved device names on EVERY segment
        $walk = $this->rootPath;
        foreach (explode('/', $normalized) as $segment) {
            if (
                '' === $segment
                || '.' === $segment
                || str_ends_with($segment, '.')
                || str_ends_with($segment, ' ')
                || 1 === preg_match('/\A(?:con|prn|aux|nul|com[1-9]|lpt[1-9])(?:\.|\z)/i', $segment)
            ) {
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

    private function isReadablePath(string $canonicalRelative): bool
    {
        $lower = strtolower($canonicalRelative);
        $base = strtolower(basename($canonicalRelative));

        $forbiddenExt = (bool) preg_match('/\.(pem|key|p12|pfx|jks|kdbx|ppk|pk8|db|sqlite3?(?:-(?:wal|shm))?)\z/', $base);
        $forbiddenName = ((str_starts_with($base, '.env') || str_ends_with($base, '.env')) && '.env.example' !== $base)
            || in_array($base, self::FORBIDDEN_SECRET_FILES, true)
            || '.git' === $lower
            || str_ends_with($lower, '/.git')
            || str_starts_with($lower, '.git/')
            || str_contains($lower, '/.git/')
            || str_starts_with($lower, '.ssh/')
            || str_contains($lower, '/.ssh/')
            || '.aws/credentials' === $lower
            || str_contains($lower, '/.aws/credentials')
            || '.aws/config' === $lower
            || str_contains($lower, '/.aws/config')
            || str_starts_with($lower, 'var/cache/')
            || str_starts_with($lower, 'var/storage/')
            || str_starts_with($lower, 'storage/dev/')
            || str_starts_with($lower, 'storage/incidents/')
            || 1 === preg_match('#/(?:var/(?:cache|storage)|storage/(?:dev|incidents))/#', '/' . $lower)
            || '.docker/config.json' === $lower
            || str_contains($lower, '/.docker/config.json');

        return !$forbiddenExt && !$forbiddenName;
    }

    private function assertReadablePath(string $canonicalRelative): void
    {
        if (!$this->isReadablePath($canonicalRelative)) {
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
            || str_starts_with($lower, '.kongono/')
            || str_contains($lower, '/.kongono/')
            || str_starts_with($lower, '.vscode/')
            || str_contains($lower, '/.vscode/')
            || str_starts_with($lower, '.idea/')
            || str_contains($lower, '/.idea/')
            || str_starts_with($lower, '.zed/')
            || str_contains($lower, '/.zed/')
            || str_starts_with($lower, '.windsurf/')
            || str_contains($lower, '/.windsurf/')
            || str_starts_with($lower, '.claude/')
            || str_contains($lower, '/.claude/')
            || str_starts_with($lower, '.agents/')
            || str_contains($lower, '/.agents/')
            || str_starts_with($lower, '.cursor/')
            || str_contains($lower, '/.cursor/')
            || str_starts_with($lower, 'vendor/')
            || str_starts_with($lower, 'deploy/')
            || str_starts_with($lower, 'bin/')
            || in_array($base, [
                'agents.md',
                'claude.md',
                'composer.json',
                'composer.lock',
                'package.json',
                'package-lock.json',
                'makefile',
                '.mcp.json',
                '.gitlab-ci.yml',
                'dockerfile',
                'compose.yml',
                'compose.yaml',
                'caddyfile',
                '.htaccess',
            ], true)
            || str_starts_with($base, 'dockerfile.')
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
