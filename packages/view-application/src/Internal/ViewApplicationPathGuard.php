<?php

declare(strict_types=1);

namespace Kongono\ViewApplication\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewApplication\Definition\ViewApplicationDefinition;
use Kongono\ViewApplication\Exception\InvalidViewApplicationException;
use Kongono\ViewApplication\Exception\UnsafeViewSourceException;

/** Admits explicit canonical contained paths and performs one stable source read. */
#[Api(ApiStatus::Internal)]
final class ViewApplicationPathGuard
{
    public const int MAX_SOURCE_BYTES = 1_048_576;

    /** @return array{definition:ViewApplicationDefinition,source:array{bytes:string,size:int,sha256:string,stat:array<string|int,mixed>}} */
    public static function resolve(ViewApplicationDefinition $definition): array
    {
        $root = self::directory($definition->applicationRoot(), 'invalid_root');
        $source = self::file($root, $definition->sourceFile());
        $output = self::directory($definition->artifactOutputRoot(), 'invalid_output');
        if ($output === $root || !str_starts_with($output, $root . '/')) {
            throw InvalidViewApplicationException::invalid('outside_root');
        }
        self::ancestors($root, $output);

        return [
            'definition' => new ViewApplicationDefinition($root, $source['absolute'], $output),
            'source' => $source,
        ];
    }

    /** @return array{absolute:string,bytes:string,size:int,sha256:string,stat:array<string|int,mixed>} */
    private static function file(string $root, string $path): array
    {
        $normalized = self::normalize($path);
        if (!self::absolute($normalized) || self::streamSyntax($normalized) || !str_starts_with($normalized, $root . '/')) {
            throw InvalidViewApplicationException::invalid('invalid_source');
        }
        self::segments($normalized);
        $resolved = realpath($path);
        if (false === $resolved) {
            throw UnsafeViewSourceException::unsafe('missing');
        }
        $absolute = self::normalize($resolved);
        if ($absolute !== $normalized || !str_starts_with($absolute, $root . '/')) {
            throw UnsafeViewSourceException::unsafe('linked');
        }
        self::ancestors($root, $absolute);
        $lstat = @lstat($path);
        if (false === $lstat || 0100000 !== ($lstat['mode'] & 0170000) || !is_file($path)) {
            throw UnsafeViewSourceException::unsafe('nonordinary');
        }
        if (is_link($path)) {
            throw UnsafeViewSourceException::unsafe('linked');
        }
        if ($lstat['nlink'] > 1) {
            throw UnsafeViewSourceException::unsafe('hard_linked');
        }
        $before = @stat($path);
        if (false === $before || $before['size'] < 1) {
            throw UnsafeViewSourceException::unsafe('unreadable');
        }
        if ($before['size'] > self::MAX_SOURCE_BYTES) {
            throw UnsafeViewSourceException::unsafe('oversized');
        }
        $handle = @fopen($path, 'rb');
        if (false === $handle) {
            throw UnsafeViewSourceException::unsafe('unreadable');
        }
        $bytes = '';
        try {
            while (!feof($handle)) {
                $chunk = @fread($handle, max(1, min(8192, self::MAX_SOURCE_BYTES + 1 - strlen($bytes))));
                if (false === $chunk) {
                    throw UnsafeViewSourceException::unsafe('unreadable');
                }
                $bytes .= $chunk;
                if (strlen($bytes) > self::MAX_SOURCE_BYTES) {
                    throw UnsafeViewSourceException::unsafe('oversized');
                }
            }
            $after = @fstat($handle);
        } finally {
            @fclose($handle);
        }
        if (false === $after || !self::sameMetadata($before, $after) || strlen($bytes) !== $before['size']) {
            throw UnsafeViewSourceException::unsafe('changed_during_read');
        }
        if (str_starts_with($bytes, "\xEF\xBB\xBF") || str_contains($bytes, "\r") || str_contains($bytes, "\0") || 1 !== preg_match('//u', $bytes) || !str_ends_with($bytes, "\n") || str_ends_with($bytes, "\n\n")) {
            throw UnsafeViewSourceException::unsafe('invalid_bytes');
        }

        return ['absolute' => $absolute, 'bytes' => $bytes, 'size' => strlen($bytes), 'sha256' => hash('sha256', $bytes), 'stat' => $before];
    }

    private static function directory(string $path, string $violation): string
    {
        $normalized = self::normalize($path);
        if (!self::absolute($normalized) || self::streamSyntax($normalized)) {
            throw InvalidViewApplicationException::invalid($violation);
        }
        self::segments($normalized);
        $resolved = realpath($path);
        if (false === $resolved || self::normalize($resolved) !== $normalized || !is_dir($path) || is_link($path)) {
            throw InvalidViewApplicationException::invalid($violation);
        }
        return rtrim($normalized, '/');
    }

    private static function ancestors(string $root, string $path): void
    {
        $relative = ltrim(substr($path, strlen($root)), '/');
        $current = rtrim($root, '/');
        foreach (explode('/', $relative) as $segment) {
            if ('' === $segment) {
                continue;
            }
            $current .= '/' . $segment;
            $resolved = realpath($current);
            if (is_link($current) || false === $resolved || self::normalize($resolved) !== $current) {
                throw InvalidViewApplicationException::invalid('outside_root');
            }
        }
    }

    private static function segments(string $path): void
    {
        $rest = 1 === preg_match('/\A[a-z]:\//D', $path) ? substr($path, 3) : ltrim($path, '/');
        foreach (explode('/', $rest) as $segment) {
            if ('' === $segment || '.' === $segment || '..' === $segment) {
                throw InvalidViewApplicationException::invalid('outside_root');
            }
        }
    }

    /**
     * @param array<array-key, mixed> $left
     * @param array<array-key, mixed> $right
     */
    private static function sameMetadata(array $left, array $right): bool
    {
        foreach (['dev', 'ino', 'mode', 'size', 'mtime', 'ctime'] as $key) {
            if (($left[$key] ?? null) !== ($right[$key] ?? null)) {
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

    private static function normalize(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);
        if (1 === preg_match('/\A[A-Z]:\//D', $normalized)) {
            $normalized = strtolower($normalized[0]) . substr($normalized, 1);
        }

        return rtrim($normalized, '/');
    }

    private function __construct() {}
}
