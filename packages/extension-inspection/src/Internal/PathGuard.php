<?php

declare(strict_types=1);

namespace Kongono\ExtensionInspection\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionInspection\Exception\ExtensionInspectionLoadException;

/** Admits explicit canonical ordinary paths and performs bounded stable reads. */
#[Api(ApiStatus::Internal)]
final class PathGuard
{
    public const int MAX_DECLARATION_BYTES = 4_194_304;
    public const int MAX_WIRING_BYTES = 4_194_304;

    public static function readDeclaration(string $path): string
    {
        return self::read($path, self::MAX_DECLARATION_BYTES, true);
    }

    public static function readWiring(string $path): string
    {
        return self::read($path, self::MAX_WIRING_BYTES, false);
    }

    private static function read(string $path, int $limit, bool $declaration): string
    {
        $canonical = self::canonical($path, $declaration);
        $resolved = realpath($path);
        if (false === $resolved || self::normalize($resolved) !== $canonical || !is_file($path) || is_link($path)) {
            self::failure($declaration, 'ordinary_file');
        }
        self::ancestors($canonical, $declaration);
        $before = @stat($canonical);
        if (false === $before || $before['size'] < 0 || $before['size'] > $limit) {
            self::failure($declaration, 'bounded_read');
        }
        $handle = @fopen($canonical, 'rb');
        if (false === $handle) {
            self::failure($declaration, 'bounded_read');
        }
        $bytes = '';
        try {
            while (!feof($handle)) {
                $chunk = fread($handle, max(1, min(8192, $limit + 1 - strlen($bytes))));
                if (false === $chunk) {
                    self::failure($declaration, 'bounded_read');
                }
                $bytes .= $chunk;
                if (strlen($bytes) > $limit) {
                    self::failure($declaration, 'bounded_read');
                }
            }
            $after = fstat($handle);
        } finally {
            fclose($handle);
        }
        if (false === $after || !self::same($before, $after) || strlen($bytes) !== $before['size']) {
            self::failure($declaration, 'stable_read');
        }
        $resolvedAfter = realpath($canonical);
        if (false === $resolvedAfter || self::normalize($resolvedAfter) !== $canonical || is_link($canonical) || !is_file($canonical)) {
            self::failure($declaration, 'stable_read');
        }
        self::ancestors($canonical, $declaration);
        return $bytes;
    }

    private static function canonical(string $path, bool $declaration): string
    {
        $normalized = self::normalize($path);
        $absolute = 1 === preg_match('/\A[A-Za-z]:\//D', $normalized) || str_starts_with($normalized, '/');
        $withoutDrive = 1 === preg_match('/\A[A-Za-z]:\//D', $normalized) ? substr($normalized, 2) : $normalized;
        if ('' === $path || !$absolute || ($normalized !== $path && str_contains($path, '/')) || str_contains($path, "\0") || str_contains($withoutDrive, ':') || str_starts_with($normalized, '//')) {
            self::failure($declaration, 'path');
        }
        foreach (explode('/', trim($withoutDrive, '/')) as $segment) {
            if ('' === $segment || '.' === $segment || '..' === $segment) {
                self::failure($declaration, 'path');
            }
        }
        return $normalized;
    }

    private static function ancestors(string $path, bool $declaration): void
    {
        $drive = 1 === preg_match('/\A[A-Za-z]:\//D', $path) ? substr($path, 0, 2) : '';
        $rest = '' !== $drive ? substr($path, 3) : ltrim($path, '/');
        $prefix = '' !== $drive ? $drive . '/' : '/';
        foreach (explode('/', $rest) as $segment) {
            $prefix = rtrim($prefix, '/') . '/' . $segment;
            $linkTarget = @readlink($prefix);
            $real = realpath($prefix);
            if (is_link($prefix) || (is_string($linkTarget) && self::normalize(rtrim($linkTarget, '/\\')) !== $prefix) || (false !== $real && self::normalize($real) !== $prefix)) {
                self::failure($declaration, 'ordinary_file');
            }
        }
    }

    /**
     * @param array<string|int, mixed> $before
     * @param array<string|int, mixed> $after
     */
    private static function same(array $before, array $after): bool
    {
        foreach (['dev', 'ino', 'mode', 'size', 'mtime', 'ctime'] as $key) {
            if (($before[$key] ?? null) !== ($after[$key] ?? null)) {
                return false;
            }
        }
        return true;
    }

    private static function normalize(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    private static function failure(bool $declaration, string $component): never
    {
        throw $declaration ? ExtensionInspectionLoadException::declaration($component) : ExtensionInspectionLoadException::wiring($component);
    }

    private function __construct() {}
}
