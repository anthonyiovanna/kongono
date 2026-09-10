<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceBindingBuild\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Admits one canonical existing ordinary writable directory and its ancestors. */
#[Api(ApiStatus::Internal)]
final class PathGuard
{
    public static function publicationDirectory(string $path): ?string
    {
        $normalized = self::normalize($path);
        $windowsAbsolute = 1 === preg_match('/\A[A-Za-z]:\//D', $normalized);
        $absolute = $windowsAbsolute || str_starts_with($normalized, '/');
        $withoutDrive = $windowsAbsolute ? substr($normalized, 2) : $normalized;
        if (
            '' === $path
            || !$absolute
            || ($normalized !== $path && str_contains($path, '/'))
            || 1 === preg_match('/[\x00-\x1F\x7F]/D', $path)
            || str_contains($withoutDrive, ':')
            || str_starts_with($normalized, '//')
        ) {
            return null;
        }
        $segments = explode('/', trim($withoutDrive, '/'));
        foreach ($segments as $segment) {
            if ('' === $segment || '.' === $segment || '..' === $segment) {
                return null;
            }
        }
        $resolved = realpath($path);
        if (false === $resolved || self::normalize($resolved) !== $normalized || !is_dir($path) || !is_writable($path) || is_link($path)) {
            return null;
        }

        $drive = $windowsAbsolute ? substr($normalized, 0, 2) : '';
        $rest = '' !== $drive ? substr($normalized, 3) : ltrim($normalized, '/');
        $prefix = '' !== $drive ? $drive . '/' : '/';
        foreach (explode('/', $rest) as $segment) {
            $prefix = rtrim($prefix, '/') . '/' . $segment;
            $linkTarget = @readlink($prefix);
            $real = realpath($prefix);
            if (
                is_link($prefix)
                || (is_string($linkTarget) && self::normalize(rtrim($linkTarget, '/\\')) !== $prefix)
                || (false !== $real && self::normalize($real) !== $prefix)
            ) {
                return null;
            }
        }

        return rtrim($normalized, '/');
    }

    private static function normalize(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    private function __construct() {}
}
