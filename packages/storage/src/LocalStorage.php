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

    private string $root;

    public function __construct(string $rootDirectory, bool $createIfMissing = true)
    {
        if (!is_dir($rootDirectory)) {
            if (!$createIfMissing || (!@mkdir($rootDirectory, 0o775, true) && !is_dir($rootDirectory))) {
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
        $path = $this->objectPath($cleanKey);
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

    public function putIfAbsent(string $key, string $contents, string $contentType = 'application/octet-stream', array $metadata = []): WriteResult
    {
        $cleanKey = $this->normalizeKey($key);
        if (is_file($this->objectPath($cleanKey))) {
            return new WriteResult(StorageOutcome::AlreadyExists, $this->meta($cleanKey));
        }

        return $this->put($cleanKey, $contents, $contentType, $metadata);
    }

    public function replaceIfVersion(string $key, string $expectedVersion, string $contents, string $contentType = 'application/octet-stream', array $metadata = []): WriteResult
    {
        $cleanKey = $this->normalizeKey($key);
        $current = $this->meta($cleanKey);
        if (null === $current) {
            return new WriteResult(StorageOutcome::Absent);
        }
        if (!hash_equals($current->version, $expectedVersion)) {
            return new WriteResult(StorageOutcome::Conflict, $current);
        }

        return $this->put($cleanKey, $contents, $contentType, $metadata);
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

    public function meta(string $key): ?ObjectMeta
    {
        $cleanKey = $this->normalizeKey($key);
        $path = $this->objectPath($cleanKey);
        if (!is_file($path)) {
            return null;
        }
        $this->assertContainedExisting($path);

        $metaFile = $this->metaPath($cleanKey);
        if (is_file($metaFile)) {
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
        $path = $this->objectPath($cleanKey);
        if (!is_file($path)) {
            return StorageOutcome::Absent;
        }
        $this->assertContainedExisting($path);

        if (null !== $expectedVersion) {
            $current = $this->meta($cleanKey);
            if (null === $current || !hash_equals($current->version, $expectedVersion)) {
                return StorageOutcome::Conflict;
            }
        }

        if (!@unlink($path)) {
            return StorageOutcome::Uncertain;
        }
        $metaPath = $this->metaPath($cleanKey);
        if (is_file($metaPath)) {
            @unlink($metaPath);
        }

        return StorageOutcome::Deleted;
    }

    public function list(string $prefix = ''): array
    {
        $cleanPrefix = ltrim(str_replace('\\', '/', trim($prefix)), '/');
        $objects = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->root));

        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo || !$file->isFile()) {
                continue;
            }
            $full = $file->getPathname();
            if (str_ends_with($full, self::META_SUFFIX) || str_ends_with($full, '.tmp')) {
                continue;
            }
            $relative = str_replace('\\', '/', substr($full, strlen($this->root) + 1));
            if ('' !== $cleanPrefix && !str_starts_with($relative, $cleanPrefix)) {
                continue;
            }
            $meta = $this->meta($relative);
            if (null !== $meta) {
                $objects[] = $meta;
            }
        }

        usort($objects, static fn(ObjectMeta $a, ObjectMeta $b): int => strcmp($a->key, $b->key));

        return $objects;
    }

    private function normalizeKey(string $key): string
    {
        $clean = ltrim(str_replace('\\', '/', trim($key)), '/');
        if ('' === $clean || str_contains($clean, '..') || str_contains($clean, "\0") || str_ends_with($clean, self::META_SUFFIX)) {
            throw new StorageException(sprintf('Invalid storage key "%s".', $key));
        }

        return $clean;
    }

    private function objectPath(string $cleanKey): string
    {
        return $this->root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $cleanKey);
    }

    private function metaPath(string $cleanKey): string
    {
        return $this->objectPath($cleanKey) . self::META_SUFFIX;
    }

    private function assertContainedExisting(string $path): void
    {
        if (is_link($path)) {
            throw new StorageException('Symlinks are not permitted inside contained storage.');
        }
        $real = realpath($path);
        $prefix = rtrim($this->root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (false === $real || !str_starts_with($real, $prefix)) {
            throw new StorageException('Resolved storage path escapes the configured storage root.');
        }
    }

    private function atomicWrite(string $targetPath, string $bytes): void
    {
        $dir = dirname($targetPath);
        if (!is_dir($dir) && !@mkdir($dir, 0o775, true) && !is_dir($dir)) {
            throw new StorageException(sprintf('Unable to create storage subdirectory "%s".', $dir));
        }
        $tmp = $targetPath . '.' . bin2hex(random_bytes(6)) . '.tmp';
        if (false === file_put_contents($tmp, $bytes, LOCK_EX)) {
            throw new StorageException('Failed to write temporary storage file.');
        }
        if (!@rename($tmp, $targetPath)) {
            @unlink($targetPath);
            if (!@rename($tmp, $targetPath)) {
                @unlink($tmp);
                throw new StorageException('Failed to atomically publish storage file.');
            }
        }
    }
}
