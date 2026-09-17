<?php

declare(strict_types=1);

namespace Kongono\CacheApplication\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheApplication\Definition\CacheApplicationDefinition;
use Kongono\CacheApplication\Exception\InvalidCacheApplicationException;
use Kongono\CacheApplication\Exception\UnsafeCacheSourceException;

/** Admits canonical contained paths and performs stable bounded ordinary-file reads. */
#[Api(ApiStatus::Internal)]
final class CacheApplicationPathGuard
{
    public const int MAX_SOURCE_BYTES = 1_048_576;

    /** @return array{definition:CacheApplicationDefinition,source_bytes:string} */
    public static function resolve(CacheApplicationDefinition $definition): array
    {
        $root = self::directory($definition->applicationRoot(), 'invalid_root');
        self::relative($definition->sourceFile());
        $sourcePath = $root . '/' . $definition->sourceFile();
        $source = self::stableFile($root, $sourcePath, self::MAX_SOURCE_BYTES, true);
        $output = self::directory($definition->outputDirectory(), 'invalid_output');
        if ($output === $root || !str_starts_with($output, $root . '/')) {
            throw InvalidCacheApplicationException::invalid('outside_root');
        }
        self::ancestors($root, $output);

        return ['definition' => new CacheApplicationDefinition($root, $definition->sourceFile(), $output), 'source_bytes' => $source];
    }

    public static function readArtifact(string $root, string $path, int $maximum): string
    {
        return self::stableFile($root, $path, $maximum, false);
    }

    public static function normalize(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        if (1 === preg_match('/\A[A-Z]:\//D', $path)) {
            $path = strtolower($path[0]) . substr($path, 1);
        }
        return rtrim($path, '/');
    }

    private static function relative(string $path): void
    {
        if ('' === $path || strlen($path) > 1024 || str_contains($path, "\0") || str_contains($path, '\\') || str_contains($path, ':') || str_contains($path, '?') || str_contains($path, '#') || str_starts_with($path, '/')) {
            throw InvalidCacheApplicationException::invalid('invalid_source_file');
        }
        foreach (explode('/', $path) as $segment) {
            if ('' === $segment || '.' === $segment || '..' === $segment) {
                throw InvalidCacheApplicationException::invalid('invalid_source_file');
            }
        }
    }

    private static function directory(string $path, string $violation): string
    {
        $normalized = self::normalize($path);
        if (!self::absolute($normalized) || self::stream($normalized)) {
            throw InvalidCacheApplicationException::invalid($violation);
        }
        $resolved = realpath($path);
        if (false === $resolved || self::normalize($resolved) !== $normalized || !is_dir($path) || is_link($path)) {
            throw InvalidCacheApplicationException::invalid($violation);
        }
        return $normalized;
    }

    private static function stableFile(string $root, string $path, int $maximum, bool $text): string
    {
        $normalized = self::normalize($path);
        if (!str_starts_with($normalized, $root . '/') || self::stream($normalized)) {
            throw InvalidCacheApplicationException::invalid('outside_root');
        }
        $resolved = realpath($path);
        if (false === $resolved) {
            throw UnsafeCacheSourceException::unsafe('missing');
        }
        if (self::normalize($resolved) !== $normalized || is_link($path)) {
            throw UnsafeCacheSourceException::unsafe('linked');
        }
        self::ancestors($root, $normalized);
        $lstat = @lstat($path);
        if (false === $lstat || 0100000 !== ($lstat['mode'] & 0170000) || !is_file($path)) {
            throw UnsafeCacheSourceException::unsafe('nonordinary');
        }
        if ($lstat['nlink'] > 1) {
            throw UnsafeCacheSourceException::unsafe('hard_linked');
        }
        $before = @stat($path);
        if (false === $before || $before['size'] < ($text ? 1 : 0)) {
            throw UnsafeCacheSourceException::unsafe('unreadable');
        }
        if ($before['size'] > $maximum) {
            throw UnsafeCacheSourceException::unsafe('oversized');
        }
        $handle = @fopen($path, 'rb');
        if (false === $handle) {
            throw UnsafeCacheSourceException::unsafe('unreadable');
        }
        $bytes = '';
        try {
            while (!feof($handle)) {
                $chunk = @fread($handle, max(1, min(8192, $maximum + 1 - strlen($bytes))));
                if (false === $chunk) {
                    throw UnsafeCacheSourceException::unsafe('unreadable');
                }
                $bytes .= $chunk;
                if (strlen($bytes) > $maximum) {
                    throw UnsafeCacheSourceException::unsafe('oversized');
                }
            }
            $after = @fstat($handle);
        } finally {
            @fclose($handle);
        }
        if (false === $after || strlen($bytes) !== $before['size']) {
            throw UnsafeCacheSourceException::unsafe('changed_during_read');
        }
        foreach (['dev', 'ino', 'mode', 'size', 'mtime', 'ctime'] as $key) {
            if ($before[$key] !== $after[$key]) {
                throw UnsafeCacheSourceException::unsafe('changed_during_read');
            }
        }
        if ($text && (str_starts_with($bytes, "\xEF\xBB\xBF") || str_contains($bytes, "\r") || str_contains($bytes, "\0") || 1 !== preg_match('//u', $bytes) || !str_ends_with($bytes, "\n") || str_ends_with($bytes, "\n\n"))) {
            throw UnsafeCacheSourceException::unsafe('invalid_bytes');
        }
        return $bytes;
    }

    private static function ancestors(string $root, string $path): void
    {
        $relative = ltrim(substr($path, strlen($root)), '/');
        $current = $root;
        foreach (explode('/', $relative) as $segment) {
            if ('' === $segment) {
                continue;
            }
            $current .= '/' . $segment;
            $resolved = realpath($current);
            if (is_link($current) || false === $resolved || self::normalize($resolved) !== $current) {
                throw InvalidCacheApplicationException::invalid('outside_root');
            }
        }
    }

    private static function absolute(string $path): bool
    {
        return str_starts_with($path, '/') || 1 === preg_match('/\A[a-z]:\//D', $path);
    }
    private static function stream(string $path): bool
    {
        $rest = 1 === preg_match('/\A[a-z]:\//D', $path) ? substr($path, 2) : $path;
        return str_starts_with($path, '//') || str_contains($rest, ':');
    }
    private function __construct() {}
}
