<?php

declare(strict_types=1);

namespace Kongono\StorageBuild\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageBuild\Exception\StorageProfilePublicationException;

/** Admits one explicit existing canonical ordinary publication root. */
#[Api(ApiStatus::Internal)]
final class PathGuard
{
    public static function publicationRoot(string $path): string
    {
        $normalized = self::normalize($path);
        $windows = 1 === preg_match('/\A[A-Za-z]:\//D', $normalized);
        if ('' === $path
            || (!$windows && !str_starts_with($normalized, '/'))
            || str_starts_with($normalized, '//')
            || 1 === preg_match('/[\x00-\x1F\x7F*?\[\]{}]/D', $path)
            || (str_contains($path, '/') && str_contains($path, '\\'))) {
            throw StorageProfilePublicationException::failed('invalid_publication_root');
        }
        $withoutRoot = $windows ? substr($normalized, 3) : ltrim($normalized, '/');
        if ('' === $withoutRoot || str_contains($withoutRoot, ':')) {
            throw StorageProfilePublicationException::failed('invalid_publication_root');
        }
        foreach (explode('/', $withoutRoot) as $segment) {
            if ('' === $segment || '.' === $segment || '..' === $segment || self::deviceName($segment)) {
                throw StorageProfilePublicationException::failed('invalid_publication_root');
            }
        }
        $resolved = realpath($path);
        if (false === $resolved || self::normalize($resolved) !== $normalized || !is_dir($path) || !is_writable($path) || is_link($path)) {
            throw StorageProfilePublicationException::failed('invalid_publication_root');
        }
        self::ancestors($normalized);

        return rtrim($normalized, '/');
    }

    public static function normalize(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);
        if (1 === preg_match('/\A[A-Z]:\//D', $normalized)) {
            $normalized = strtolower($normalized[0]) . substr($normalized, 1);
        }

        return $normalized;
    }

    private static function ancestors(string $path): void
    {
        $windows = 1 === preg_match('/\A[A-Za-z]:\//D', $path);
        $root = $windows ? substr($path, 0, 3) : '/';
        $rest = $windows ? substr($path, 3) : ltrim($path, '/');
        $current = rtrim($root, '/');
        foreach (explode('/', $rest) as $segment) {
            $current .= '/' . $segment;
            $resolved = realpath($current);
            $stat = @lstat($current);
            if (false === $stat || 0040000 !== ($stat['mode'] & 0170000) || is_link($current) || (false !== $resolved && self::normalize($resolved) !== $current)) {
                throw StorageProfilePublicationException::failed('invalid_publication_root');
            }
        }
    }

    private static function deviceName(string $segment): bool
    {
        $base = strtolower(explode('.', $segment, 2)[0]);

        return in_array($base, ['con', 'prn', 'aux', 'nul', 'clock$', 'com1', 'com2', 'com3', 'com4', 'com5', 'com6', 'com7', 'com8', 'com9', 'lpt1', 'lpt2', 'lpt3', 'lpt4', 'lpt5', 'lpt6', 'lpt7', 'lpt8', 'lpt9'], true);
    }

    private function __construct() {}
}
