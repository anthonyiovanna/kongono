<?php

declare(strict_types=1);

namespace Kongono\AssetApplication\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetApplication\Definition\AssetApplicationDefinition;
use Kongono\AssetApplication\Exception\InvalidAssetApplicationException;
use Kongono\AssetApplication\Exception\UnsafeAssetSourceException;

/** Admits canonical contained paths and performs stable bounded ordinary-file reads. */
#[Api(ApiStatus::Internal)]
final class AssetApplicationPathGuard
{
    public const int MAX_DEFINITION_BYTES = 1_048_576;
    public const int MAX_SOURCE_BYTES = 67_108_864;

    /** @return array{definition:AssetApplicationDefinition,definition_bytes:string} */
    public static function resolve(AssetApplicationDefinition $definition): array
    {
        $root = self::directory($definition->applicationRoot(), 'invalid_root');
        $source = self::absoluteContainedFile($root, $definition->sourceFile(), self::MAX_DEFINITION_BYTES, true);
        $output = self::directory($definition->outputDirectory(), 'invalid_output');
        if ($output === $root || !str_starts_with($output, $root . '/')) {
            throw InvalidAssetApplicationException::invalid('outside_root');
        }
        self::ancestors($root, $output);

        return [
            'definition' => new AssetApplicationDefinition($root, $source['absolute'], $output),
            'definition_bytes' => $source['bytes'],
        ];
    }

    /** @return array{absolute:string,bytes:string,size:int} */
    public static function readRelativeSource(string $root, string $relative): array
    {
        self::relativePath($relative);

        return self::absoluteContainedFile($root, $root . '/' . $relative, self::MAX_SOURCE_BYTES, false);
    }

    public static function normalize(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);
        if (1 === preg_match('/\A[A-Z]:\//D', $normalized)) {
            $normalized = strtolower($normalized[0]) . substr($normalized, 1);
        }

        return rtrim($normalized, '/');
    }

    private static function relativePath(string $path): void
    {
        if ('' === $path || strlen($path) > 1024 || str_contains($path, "\0") || str_contains($path, '\\') || str_contains($path, ':') || str_contains($path, '?') || str_contains($path, '#') || str_starts_with($path, '/') || str_starts_with($path, '//')) {
            throw InvalidAssetApplicationException::invalid('invalid_declaration');
        }
        foreach (explode('/', $path) as $segment) {
            if ('' === $segment || '.' === $segment || '..' === $segment) {
                throw InvalidAssetApplicationException::invalid('invalid_declaration');
            }
        }
    }

    /** @return array{absolute:string,bytes:string,size:int} */
    private static function absoluteContainedFile(string $root, string $path, int $limit, bool $text): array
    {
        $normalized = self::normalize($path);
        if (!self::absolute($normalized) || self::streamSyntax($normalized) || !str_starts_with($normalized, $root . '/')) {
            throw InvalidAssetApplicationException::invalid('invalid_source_file');
        }
        self::segments($normalized);
        $resolved = realpath($path);
        if (false === $resolved) {
            throw UnsafeAssetSourceException::unsafe('missing');
        }
        $absolute = self::normalize($resolved);
        if ($absolute !== $normalized || !str_starts_with($absolute, $root . '/')) {
            throw UnsafeAssetSourceException::unsafe('linked');
        }
        self::ancestors($root, $absolute);
        $lstat = @lstat($path);
        if (false === $lstat || 0100000 !== ($lstat['mode'] & 0170000) || !is_file($path)) {
            throw UnsafeAssetSourceException::unsafe('nonordinary');
        }
        if (is_link($path)) {
            throw UnsafeAssetSourceException::unsafe('linked');
        }
        if ($lstat['nlink'] > 1) {
            throw UnsafeAssetSourceException::unsafe('hard_linked');
        }
        $before = @stat($path);
        if (false === $before || $before['size'] < ($text ? 1 : 0)) {
            throw UnsafeAssetSourceException::unsafe('unreadable');
        }
        if ($before['size'] > $limit) {
            throw UnsafeAssetSourceException::unsafe('oversized');
        }
        $handle = @fopen($path, 'rb');
        if (false === $handle) {
            throw UnsafeAssetSourceException::unsafe('unreadable');
        }
        $bytes = '';
        try {
            while (!feof($handle)) {
                $chunk = @fread($handle, max(1, min(8192, $limit + 1 - strlen($bytes))));
                if (false === $chunk) {
                    throw UnsafeAssetSourceException::unsafe('unreadable');
                }
                $bytes .= $chunk;
                if (strlen($bytes) > $limit) {
                    throw UnsafeAssetSourceException::unsafe('oversized');
                }
            }
            $after = @fstat($handle);
        } finally {
            @fclose($handle);
        }
        if (false === $after || !self::sameMetadata($before, $after) || strlen($bytes) !== $before['size']) {
            throw UnsafeAssetSourceException::unsafe('changed_during_read');
        }
        if ($text && (str_starts_with($bytes, "\xEF\xBB\xBF") || str_contains($bytes, "\r") || str_contains($bytes, "\0") || 1 !== preg_match('//u', $bytes) || !str_ends_with($bytes, "\n") || str_ends_with($bytes, "\n\n"))) {
            throw UnsafeAssetSourceException::unsafe('invalid_bytes');
        }

        return ['absolute' => $absolute, 'bytes' => $bytes, 'size' => strlen($bytes)];
    }

    private static function directory(string $path, string $violation): string
    {
        $normalized = self::normalize($path);
        if (!self::absolute($normalized) || self::streamSyntax($normalized)) {
            throw InvalidAssetApplicationException::invalid($violation);
        }
        self::segments($normalized);
        $resolved = realpath($path);
        if (false === $resolved || self::normalize($resolved) !== $normalized || !is_dir($path) || is_link($path)) {
            throw InvalidAssetApplicationException::invalid($violation);
        }

        return $normalized;
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
                throw InvalidAssetApplicationException::invalid('outside_root');
            }
        }
    }

    private static function segments(string $path): void
    {
        $rest = 1 === preg_match('/\A[a-z]:\//D', $path) ? substr($path, 3) : ltrim($path, '/');
        foreach (explode('/', $rest) as $segment) {
            if ('' === $segment || '.' === $segment || '..' === $segment) {
                throw InvalidAssetApplicationException::invalid('outside_root');
            }
        }
    }

    /**
     * @param array<int|string, int> $left
     * @param array<int|string, int> $right
     */
    private static function sameMetadata(array $left, array $right): bool
    {
        foreach (['dev', 'ino', 'mode', 'size', 'mtime', 'ctime'] as $key) {
            if ($left[$key] !== $right[$key]) {
                return false;
            }
        }

        return true;
    }

    private static function absolute(string $path): bool
    {
        return str_starts_with($path, '/') || 1 === preg_match('/\A[a-z]:\//D', $path);
    }

    private static function streamSyntax(string $path): bool
    {
        $withoutDrive = 1 === preg_match('/\A[a-z]:\//D', $path) ? substr($path, 2) : $path;

        return str_starts_with($path, '//') || str_contains($withoutDrive, ':');
    }

    private function __construct() {}
}
