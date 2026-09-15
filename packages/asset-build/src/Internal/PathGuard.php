<?php

declare(strict_types=1);

namespace Kongono\AssetBuild\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetBuild\Exception\AssetPublicationException;

/** Admits one explicit existing canonical ordinary publication directory. */
#[Api(ApiStatus::Internal)]
final class PathGuard
{
    public static function publicationDirectory(string $path): string
    {
        $normalized = self::normalize($path);
        $windows = 1 === preg_match('/\A[A-Za-z]:\//D', $normalized);
        if ('' === $path || (!$windows && !str_starts_with($normalized, '/')) || str_starts_with($normalized, '//') || str_contains($path, "\0") || (str_contains($path, '/') && str_contains($path, '\\'))) {
            throw AssetPublicationException::failed('invalid_output_directory');
        }
        $withoutRoot = $windows ? substr($normalized, 3) : ltrim($normalized, '/');
        if ('' === $withoutRoot || str_contains($withoutRoot, ':')) {
            throw AssetPublicationException::failed('invalid_output_directory');
        }
        foreach (explode('/', $withoutRoot) as $segment) {
            if ('' === $segment || '.' === $segment || '..' === $segment || self::deviceName($segment)) {
                throw AssetPublicationException::failed('invalid_output_directory');
            }
        }
        $resolved = realpath($path);
        if (false === $resolved || self::normalize($resolved) !== $normalized || !is_dir($path) || !is_writable($path) || is_link($path)) {
            throw AssetPublicationException::failed('invalid_output_directory');
        }
        self::ancestors($normalized);

        return rtrim($normalized, '/');
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
                throw AssetPublicationException::failed('invalid_output_directory');
            }
        }
    }

    private static function deviceName(string $segment): bool
    {
        $base = strtolower(explode('.', $segment, 2)[0]);

        return in_array($base, ['con', 'prn', 'aux', 'nul', 'clock$', 'com1', 'com2', 'com3', 'com4', 'com5', 'com6', 'com7', 'com8', 'com9', 'lpt1', 'lpt2', 'lpt3', 'lpt4', 'lpt5', 'lpt6', 'lpt7', 'lpt8', 'lpt9'], true);
    }

    public static function normalize(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);
        if (1 === preg_match('/\A[A-Z]:\//D', $normalized)) {
            $normalized = strtolower($normalized[0]) . substr($normalized, 1);
        }

        return $normalized;
    }

    private function __construct() {}
}
