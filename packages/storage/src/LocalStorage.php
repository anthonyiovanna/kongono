<?php

declare(strict_types=1);

namespace Kongono\Storage;

use JsonException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Contained, atomic, versioned local-filesystem object storage.
 *
 * Guarantees:
 *   - Strict root containment (rejects traversal, null bytes, and escaping symlinks)
 *   - Atomic temp-file write + rename publication
 *   - SHA-256 content digests & opaque random version tokens for optimistic concurrency
 */
final readonly class LocalStorage implements StorageInterface
{
    private const string META_SUFFIX = '.kgnmeta.json';
    private const string LOCK_DIR = '.kgnlocks';

    /** @var list<string> */
    private const array WINDOWS_RESERVED = [
        'con', 'prn', 'aux', 'nul',
        'com1', 'com2', 'com3', 'com4', 'com5', 'com6', 'com7', 'com8', 'com9',
        'lpt1', 'lpt2', 'lpt3', 'lpt4', 'lpt5', 'lpt6', 'lpt7', 'lpt8', 'lpt9',
    ];

    private string $root;

    public function __construct(string $rootDirectory, bool $createIfMissing = true)
    {
        $cleanRoot = rtrim($rootDirectory, '/\\');
        if ('' !== $cleanRoot && is_link($cleanRoot)) {
            throw new StorageException(sprintf('Storage root directory cannot be a symlink: "%s".', $rootDirectory));
        }
        if (!is_dir($rootDirectory)) {
            if (!$createIfMissing || (!@mkdir($rootDirectory, 0o750, true) && !is_dir($rootDirectory))) {
                throw new StorageException(sprintf('Storage root directory does not exist: "%s".', $rootDirectory));
            }
        }
        $resolved = realpath($rootDirectory);
        if (false === $resolved || !is_dir($resolved)) {
            throw new StorageException(sprintf('Unable to resolve storage root directory: "%s".', $rootDirectory));
        }
        $this->root = $resolved;
    }

    public function put(string $key, string $contents, string $contentType = 'application/octet-stream', array $metadata = []): WriteResult
    {
        $cleanKey = $this->normalizeKey($key);

        return $this->withKeyLock($cleanKey, fn(): WriteResult => $this->putUnlocked($cleanKey, $contents, $contentType, $metadata));
    }

    public function putStream(string $key, mixed $stream, string $contentType = 'application/octet-stream', array $metadata = []): WriteResult
    {
        if (!is_resource($stream)) {
            throw new StorageException('putStream() requires an open PHP stream resource.');
        }

        $cleanKey = $this->normalizeKey($key);

        return $this->withKeyLock($cleanKey, fn(): WriteResult => $this->putStreamUnlocked($cleanKey, $stream, $contentType, $metadata));
    }

    public function putIfAbsent(string $key, string $contents, string $contentType = 'application/octet-stream', array $metadata = []): WriteResult
    {
        $cleanKey = $this->normalizeKey($key);

        return $this->withKeyLock($cleanKey, function () use ($cleanKey, $contents, $contentType, $metadata): WriteResult {
            $path = $this->objectPath($cleanKey);
            if (is_file($path)) {
                $this->assertContainedExisting($path);

                return new WriteResult(StorageOutcome::AlreadyExists, $this->metaUnlocked($cleanKey));
            }

            return $this->putUnlocked($cleanKey, $contents, $contentType, $metadata);
        });
    }

    public function replaceIfVersion(string $key, string $expectedVersion, string $contents, string $contentType = 'application/octet-stream', array $metadata = []): WriteResult
    {
        $cleanKey = $this->normalizeKey($key);

        return $this->withKeyLock($cleanKey, function () use ($cleanKey, $expectedVersion, $contents, $contentType, $metadata): WriteResult {
            $current = $this->metaUnlocked($cleanKey);
            if (null === $current) {
                return new WriteResult(StorageOutcome::Absent);
            }
            if (!hash_equals($current->version, $expectedVersion)) {
                return new WriteResult(StorageOutcome::Conflict, $current);
            }

            return $this->putUnlocked($cleanKey, $contents, $contentType, $metadata);
        });
    }

    public function get(string $key): ?string
    {
        $cleanKey = $this->normalizeKey($key);
        $path = $this->objectPath($cleanKey);
        if (!is_file($path)) {
            return null;
        }
        $this->assertContainedExisting($path);
        $bytes = file_get_contents($path);
        if (false === $bytes) {
            throw new StorageException(sprintf('Unable to read stored object "%s".', $cleanKey));
        }

        return $bytes;
    }

    public function getStream(string $key): mixed
    {
        $cleanKey = $this->normalizeKey($key);
        $path = $this->objectPath($cleanKey);
        if (!is_file($path)) {
            return null;
        }
        $this->assertContainedExisting($path);
        $handle = @fopen($path, 'rb');
        if (false === $handle) {
            throw new StorageException(sprintf('Unable to open stored object stream "%s".', $cleanKey));
        }

        return $handle;
    }

    public function copy(string $fromKey, string $toKey): WriteResult
    {
        $cleanFrom = $this->normalizeKey($fromKey);
        $cleanTo = $this->normalizeKey($toKey);
        $srcMeta = $this->metaUnlocked($cleanFrom);
        if (null === $srcMeta) {
            return new WriteResult(StorageOutcome::Absent);
        }
        if ($cleanFrom === $cleanTo) {
            return new WriteResult(StorageOutcome::Replaced, $srcMeta);
        }
        if (0 === strcasecmp($cleanFrom, $cleanTo)) {
            $realFrom = realpath($this->objectPath($cleanFrom));
            $realTo = realpath($this->objectPath($cleanTo));
            if (false !== $realFrom && $realFrom === $realTo) {
                return new WriteResult(StorageOutcome::Replaced, $srcMeta);
            }
        }

        $srcStream = $this->getStream($cleanFrom);
        if (null === $srcStream) {
            return new WriteResult(StorageOutcome::Absent);
        }

        try {
            return $this->putStream($cleanTo, $srcStream, $srcMeta->contentType, $srcMeta->metadata);
        } finally {
            if (is_resource($srcStream)) {
                fclose($srcStream);
            }
        }
    }

    public function move(string $fromKey, string $toKey): WriteResult
    {
        $cleanFrom = $this->normalizeKey($fromKey);
        $cleanTo = $this->normalizeKey($toKey);
        if ($cleanFrom === $cleanTo) {
            $existing = $this->metaUnlocked($cleanFrom);

            return null !== $existing
                ? new WriteResult(StorageOutcome::Replaced, $existing)
                : new WriteResult(StorageOutcome::Absent);
        }

        // Guard case-only renames on case-insensitive filesystems (Windows/macOS) inside the shared case-folded key lock
        // so delete($cleanFrom) never destroys the freshly copied destination file.
        if (0 === strcasecmp($cleanFrom, $cleanTo)) {
            $caseRenameResult = $this->withKeyLock($cleanFrom, function () use ($cleanFrom, $cleanTo): ?WriteResult {
                $fromPath = $this->objectPath($cleanFrom);
                $toPath = $this->objectPath($cleanTo);
                $this->assertNoSymlinksInPath($fromPath);
                $this->assertNoSymlinksInPath($toPath);
                $realFrom = realpath($fromPath);
                $realTo = realpath($toPath);
                if (false === $realFrom || $realFrom !== $realTo) {
                    return null;
                }
                $existing = $this->metaUnlocked($cleanFrom);
                if (null === $existing) {
                    return new WriteResult(StorageOutcome::Absent);
                }

                $tmpPath = $fromPath . '.' . bin2hex(random_bytes(8)) . '.tmp';
                if (!@rename($fromPath, $tmpPath) || !@rename($tmpPath, $toPath)) {
                    if (is_file($tmpPath) && !is_file($fromPath)) {
                        @rename($tmpPath, $fromPath);
                    }
                    throw new StorageException(sprintf('Failed to atomically rename storage object "%s" to "%s".', $cleanFrom, $cleanTo));
                }

                $fromMetaPath = $this->metaPath($cleanFrom);
                $toMetaPath = $this->metaPath($cleanTo);
                if (is_file($fromMetaPath)) {
                    $tmpMeta = $fromMetaPath . '.' . bin2hex(random_bytes(8)) . '.tmp';
                    if (@rename($fromMetaPath, $tmpMeta)) {
                        @rename($tmpMeta, $toMetaPath);
                    }
                }
                $updated = new ObjectMeta(
                    key: $cleanTo,
                    size: $existing->size,
                    sha256: $existing->sha256,
                    version: $existing->version,
                    contentType: $existing->contentType,
                    updatedAt: gmdate(DATE_ATOM),
                    metadata: $existing->metadata,
                );
                $this->atomicWrite($toMetaPath, json_encode($updated->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));

                return new WriteResult(StorageOutcome::Replaced, $updated);
            });

            if (null !== $caseRenameResult) {
                return $caseRenameResult;
            }
        }

        $copied = $this->copy($cleanFrom, $cleanTo);
        if (!$copied->succeeded()) {
            return $copied;
        }

        $this->delete($cleanFrom);

        return $copied;
    }

    public function meta(string $key): ?ObjectMeta
    {
        return $this->metaUnlocked($this->normalizeKey($key));
    }

    public function exists(string $key): bool
    {
        $path = $this->objectPath($this->normalizeKey($key));
        if (!is_file($path)) {
            return false;
        }
        $this->assertContainedExisting($path);

        return true;
    }

    public function delete(string $key, ?string $expectedVersion = null): StorageOutcome
    {
        $cleanKey = $this->normalizeKey($key);

        return $this->withKeyLock($cleanKey, function () use ($cleanKey, $expectedVersion): StorageOutcome {
            $path = $this->objectPath($cleanKey);
            if (!is_file($path)) {
                return StorageOutcome::Absent;
            }
            $this->assertContainedExisting($path);

            if (null !== $expectedVersion) {
                $current = $this->metaUnlocked($cleanKey);
                if (null === $current || !hash_equals($current->version, $expectedVersion)) {
                    return StorageOutcome::Conflict;
                }
            }

            if (!@unlink($path)) {
                return StorageOutcome::Uncertain;
            }
            $metaPath = $this->metaPath($cleanKey);
            if (is_file($metaPath)) {
                $this->assertContainedExisting($metaPath);
                @unlink($metaPath);
            }

            return StorageOutcome::Deleted;
        });
    }

    public function list(string $prefix = ''): array
    {
        $cleanPrefix = ltrim(str_replace('\\', '/', trim($prefix)), '/');
        $objects = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->root));

        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo || !$file->isFile() || $file->isLink()) {
                continue;
            }
            $full = $file->getPathname();
            $lowerFull = strtolower($full);
            if (str_ends_with($lowerFull, self::META_SUFFIX) || str_ends_with($lowerFull, '.tmp') || str_ends_with($lowerFull, '.lock')) {
                continue;
            }
            $relative = str_replace('\\', '/', substr($full, strlen($this->root) + 1));
            if (str_starts_with(strtolower($relative), self::LOCK_DIR . '/')) {
                continue;
            }
            if ('' !== $cleanPrefix && !str_starts_with($relative, $cleanPrefix)) {
                continue;
            }
            $meta = $this->metaUnlocked($relative);
            if (null !== $meta) {
                $objects[] = $meta;
            }
        }

        usort($objects, static fn(ObjectMeta $a, ObjectMeta $b): int => strcmp($a->key, $b->key));

        return $objects;
    }

    /** @param array<string, string> $metadata */
    private function putUnlocked(string $cleanKey, string $contents, string $contentType, array $metadata): WriteResult
    {
        $this->assertSafeMetadata($contentType, $metadata);
        $path = $this->objectPath($cleanKey);
        $this->assertNoSymlinksInPath($path);
        $existed = is_file($path);

        $meta = new ObjectMeta(
            key: $cleanKey,
            size: strlen($contents),
            sha256: hash('sha256', $contents),
            version: bin2hex(random_bytes(16)),
            contentType: $contentType,
            updatedAt: gmdate(DATE_ATOM),
            metadata: $metadata,
        );

        $this->atomicWrite($path, $contents);
        $this->atomicWrite($this->metaPath($cleanKey), json_encode($meta->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));

        return new WriteResult($existed ? StorageOutcome::Replaced : StorageOutcome::Created, $meta);
    }

    /**
     * @param resource              $stream
     * @param array<string, string> $metadata
     */
    private function putStreamUnlocked(string $cleanKey, mixed $stream, string $contentType, array $metadata): WriteResult
    {
        $this->assertSafeMetadata($contentType, $metadata);
        $path = $this->objectPath($cleanKey);
        $this->assertNoSymlinksInPath($path);
        $existed = is_file($path);

        $dir = dirname($path);
        if (!is_dir($dir) && !@mkdir($dir, 0o750, true) && !is_dir($dir)) {
            throw new StorageException(sprintf('Unable to create storage subdirectory "%s".', $dir));
        }

        $realDir = realpath($dir);
        $prefix = rtrim($this->root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (false === $realDir || ($realDir !== $this->root && !str_starts_with($realDir . DIRECTORY_SEPARATOR, $prefix))) {
            throw new StorageException('Storage target directory escapes the configured storage root.');
        }
        if (is_link($path)) {
            throw new StorageException('Symlinks are not permitted inside contained storage.');
        }

        $tmp = $path . '.' . bin2hex(random_bytes(12)) . '.tmp';
        $dest = @fopen($tmp, 'xb');
        if (false === $dest) {
            throw new StorageException('Failed to create temporary storage file.');
        }

        $hashCtx = hash_init('sha256');
        $size = 0;

        try {
            @chmod($tmp, 0o600);
            while (!feof($stream)) {
                $chunk = fread($stream, 65536);
                if (false === $chunk) {
                    throw new StorageException('Failed to read from input stream.');
                }
                if ('' === $chunk) {
                    break;
                }
                $written = fwrite($dest, $chunk);
                if (false === $written || $written !== strlen($chunk)) {
                    throw new StorageException('Failed to write temporary storage file.');
                }
                hash_update($hashCtx, $chunk);
                $size += $written;
            }
            fflush($dest);
            fclose($dest);
        } catch (\Throwable $e) {
            if (is_resource($dest)) {
                fclose($dest);
            }
            @unlink($tmp);
            throw $e;
        }

        if (!@rename($tmp, $path)) {
            if ('\\' === DIRECTORY_SEPARATOR && is_file($path)) {
                @unlink($path);
                if (@rename($tmp, $path)) {
                    goto published;
                }
            }
            @unlink($tmp);
            throw new StorageException('Failed to atomically publish storage file.');
        }
        published:

        $meta = new ObjectMeta(
            key: $cleanKey,
            size: $size,
            sha256: hash_final($hashCtx),
            version: bin2hex(random_bytes(16)),
            contentType: $contentType,
            updatedAt: gmdate(DATE_ATOM),
            metadata: $metadata,
        );
        $this->atomicWrite($this->metaPath($cleanKey), json_encode($meta->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));

        return new WriteResult($existed ? StorageOutcome::Replaced : StorageOutcome::Created, $meta);
    }

    private function metaUnlocked(string $cleanKey): ?ObjectMeta
    {
        $path = $this->objectPath($cleanKey);
        if (!is_file($path)) {
            return null;
        }
        $this->assertContainedExisting($path);

        $metaFile = $this->metaPath($cleanKey);
        if (is_file($metaFile)) {
            $this->assertContainedExisting($metaFile);
            $raw = file_get_contents($metaFile);
            if (is_string($raw)) {
                try {
                    $decoded = json_decode($raw, true, 16, JSON_THROW_ON_ERROR);
                    if (is_array($decoded)) {
                        /** @var array<string, string> $custom */
                        $custom = [];
                        if (isset($decoded['metadata']) && is_array($decoded['metadata'])) {
                            foreach ($decoded['metadata'] as $k => $v) {
                                if (is_string($k) && is_string($v)) {
                                    $custom[$k] = $v;
                                }
                            }
                        }

                        return new ObjectMeta(
                            key: $cleanKey,
                            size: is_int($decoded['size'] ?? null) ? $decoded['size'] : (int) filesize($path),
                            sha256: is_string($decoded['sha256'] ?? null) ? $decoded['sha256'] : (string) hash_file('sha256', $path),
                            version: is_string($decoded['version'] ?? null) ? $decoded['version'] : '',
                            contentType: is_string($decoded['content_type'] ?? null) ? $decoded['content_type'] : 'application/octet-stream',
                            updatedAt: is_string($decoded['updated_at'] ?? null) ? $decoded['updated_at'] : gmdate(DATE_ATOM),
                            metadata: $custom,
                        );
                    }
                } catch (JsonException) {
                    // Fall back to computed metadata below
                }
            }
        }

        $size = filesize($path);
        $digest = hash_file('sha256', $path);

        return new ObjectMeta(
            key: $cleanKey,
            size: false === $size ? 0 : $size,
            sha256: false === $digest ? '' : $digest,
            version: false === $digest ? '' : substr($digest, 0, 32),
            contentType: 'application/octet-stream',
            updatedAt: gmdate(DATE_ATOM),
        );
    }

    private function normalizeKey(string $key): string
    {
        if (1 === preg_match('/[\x00-\x1f\x7f]/', $key)) {
            throw new StorageException(sprintf('Invalid storage key "%s".', $key));
        }
        $clean = ltrim(str_replace('\\', '/', trim($key)), '/');
        if (
            '' === $clean
            || str_contains($clean, ':')
        ) {
            throw new StorageException(sprintf('Invalid storage key "%s".', $key));
        }

        $segments = explode('/', $clean);
        foreach ($segments as $index => $segment) {
            if ('' === $segment || '.' === $segment || '..' === $segment || str_contains($segment, '..')) {
                throw new StorageException(sprintf('Invalid storage key "%s".', $key));
            }
            if (str_ends_with($segment, '.') || str_ends_with($segment, ' ')) {
                throw new StorageException(sprintf('Invalid storage key "%s".', $key));
            }
            $lower = strtolower($segment);
            if (0 === $index && self::LOCK_DIR === $lower) {
                throw new StorageException(sprintf('Reserved storage key "%s".', $key));
            }
            if (
                str_ends_with($lower, self::META_SUFFIX)
                || str_ends_with($lower, '.tmp')
                || str_ends_with($lower, '.lock')
            ) {
                throw new StorageException(sprintf('Reserved storage metadata suffix in key "%s".', $key));
            }
            $stem = explode('.', $lower, 2)[0];
            if (in_array($stem, self::WINDOWS_RESERVED, true)) {
                throw new StorageException(sprintf('Reserved device name in storage key "%s".', $key));
            }
        }

        return implode('/', $segments);
    }

    private function objectPath(string $cleanKey): string
    {
        return $this->root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $cleanKey);
    }

    private function metaPath(string $cleanKey): string
    {
        return $this->objectPath($cleanKey) . self::META_SUFFIX;
    }

    private function assertNoSymlinksInPath(string $targetPath): void
    {
        $prefix = rtrim($this->root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (!str_starts_with($targetPath, $prefix)) {
            throw new StorageException('Storage target path escapes the configured storage root.');
        }

        $relative = substr($targetPath, strlen($prefix));
        $current = $this->root;
        foreach (explode(DIRECTORY_SEPARATOR, $relative) as $segment) {
            if ('' === $segment) {
                continue;
            }
            $current .= DIRECTORY_SEPARATOR . $segment;
            if (is_link($current)) {
                throw new StorageException('Symlinks are not permitted inside contained storage.');
            }
        }
    }

    private function assertContainedExisting(string $path): void
    {
        $this->assertNoSymlinksInPath($path);
        $real = realpath($path);
        $prefix = rtrim($this->root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (false === $real || !str_starts_with($real, $prefix)) {
            throw new StorageException('Resolved storage path escapes the configured storage root.');
        }
    }

    private function atomicWrite(string $targetPath, string $bytes): void
    {
        $this->assertNoSymlinksInPath($targetPath);
        $dir = dirname($targetPath);
        if (!is_dir($dir) && !@mkdir($dir, 0o750, true) && !is_dir($dir)) {
            throw new StorageException(sprintf('Unable to create storage subdirectory "%s".', $dir));
        }

        $realDir = realpath($dir);
        $prefix = rtrim($this->root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (false === $realDir || ($realDir !== $this->root && !str_starts_with($realDir . DIRECTORY_SEPARATOR, $prefix))) {
            throw new StorageException('Storage target directory escapes the configured storage root.');
        }
        if (is_link($targetPath)) {
            throw new StorageException('Symlinks are not permitted inside contained storage.');
        }

        $tmp = $targetPath . '.' . bin2hex(random_bytes(12)) . '.tmp';
        $handle = @fopen($tmp, 'xb');
        if (false === $handle) {
            throw new StorageException('Failed to create temporary storage file.');
        }

        try {
            @chmod($tmp, 0o600);
            $written = fwrite($handle, $bytes);
            if (false === $written || $written !== strlen($bytes)) {
                throw new StorageException('Failed to write temporary storage file.');
            }
            fclose($handle);
        } catch (\Throwable $e) {
            if (is_resource($handle)) {
                fclose($handle);
            }
            @unlink($tmp);
            throw $e;
        }

        if (!@rename($tmp, $targetPath)) {
            if ('\\' === DIRECTORY_SEPARATOR && is_file($targetPath)) {
                @unlink($targetPath);
                if (@rename($tmp, $targetPath)) {
                    return;
                }
            }
            @unlink($tmp);
            throw new StorageException('Failed to atomically publish storage file.');
        }
    }

    /** @param array<string, string> $metadata */
    private function assertSafeMetadata(string $contentType, array $metadata): void
    {
        if ('' === trim($contentType) || false !== strpbrk($contentType, "\r\n\0")) {
            throw new StorageException('Invalid storage content type.');
        }
        foreach ($metadata as $k => $v) {
            if (false !== strpbrk($k, "\r\n\0") || false !== strpbrk($v, "\r\n\0")) {
                throw new StorageException('Storage metadata cannot contain CR, LF, or NUL bytes.');
            }
        }
    }

    /**
     * Fixed 256-shard flock pool prevents unbounded lock-file inode exhaustion
     * even under high-cardinality writes or deletes of non-existent keys.
     * Case-folds the shard key so case-insensitive filesystems (Windows/macOS) serialize `foo.txt` and `FOO.TXT`.
     *
     * @template T
     * @param \Closure(): T $fn
     * @return T
     */
    private function withKeyLock(string $cleanKey, \Closure $fn): mixed
    {
        $lockDir = $this->root . DIRECTORY_SEPARATOR . self::LOCK_DIR;
        if (is_link($lockDir)) {
            throw new StorageException('Storage lock directory cannot be a symlink.');
        }
        if (!is_dir($lockDir) && !@mkdir($lockDir, 0o700, true) && !is_dir($lockDir)) {
            throw new StorageException('Unable to create storage lock directory.');
        }

        $lockFile = $lockDir . DIRECTORY_SEPARATOR . 'bucket-' . substr(hash('sha256', strtolower($cleanKey)), 0, 2) . '.lock';
        if (is_link($lockFile)) {
            throw new StorageException('Storage lock file cannot be a symlink.');
        }

        $handle = @fopen($lockFile, 'c+b');
        if (false === $handle) {
            throw new StorageException('Unable to open storage lock file.');
        }
        @chmod($lockFile, 0o600);

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new StorageException('Unable to acquire storage key lock.');
            }

            return $fn();
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
