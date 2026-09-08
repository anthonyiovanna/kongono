<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressApplication\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressApplication\Exception\NetworkEgressWiringLoadException;
use Kongono\NetworkEgressApplication\Exception\NetworkEgressWiringPublicationException;

/** Validates explicit contained paths, links, ancestors, and Windows reparse escapes. */
#[Api(ApiStatus::Internal)]
final class PathGuard
{
    /** @return array{string, string} */
    public static function publicationLocations(string $applicationRoot, string $relativeOutputDirectory): array
    {
        try {
            $root = self::directory($applicationRoot);
            $relative = self::relative($relativeOutputDirectory);
            $output = $root . '/' . $relative;
            $resolved = realpath($output);
            if (false === $resolved || self::normalize($resolved) !== $output || !is_dir($output) || is_link($output)) {
                throw NetworkEgressWiringPublicationException::failed('path');
            }
            self::containedAncestors($root, $output);

            return [$root, $output];
        } catch (NetworkEgressWiringPublicationException $failure) {
            throw $failure;
        } catch (\Throwable $failure) {
            throw NetworkEgressWiringPublicationException::failed('path', $failure);
        }
    }

    public static function loadFile(string $applicationRoot, string $file): string
    {
        try {
            $root = self::directory($applicationRoot);
            $canonical = self::absolute($file);
            if (!str_starts_with($canonical, $root . '/') || is_link($file) || !is_file($file)) {
                throw NetworkEgressWiringLoadException::failed('path');
            }
            $resolved = realpath($file);
            if (false === $resolved || self::normalize($resolved) !== $canonical) {
                throw NetworkEgressWiringLoadException::failed('path');
            }
            self::containedAncestors($root, $canonical);

            return $canonical;
        } catch (NetworkEgressWiringLoadException $failure) {
            throw $failure;
        } catch (\Throwable $failure) {
            throw NetworkEgressWiringLoadException::failed('path', $failure);
        }
    }

    public static function applicationRoot(string $path): string
    {
        return self::directory($path);
    }

    private static function directory(string $path): string
    {
        $canonical = self::absolute($path);
        $resolved = realpath($path);
        if (false === $resolved || self::normalize($resolved) !== $canonical || !is_dir($path) || is_link($path)) {
            throw new \RuntimeException('unsafe_directory');
        }
        self::ancestors($canonical);

        return rtrim($canonical, '/');
    }

    private static function relative(string $path): string
    {
        if ('' === $path || $path !== self::normalize($path) || str_starts_with($path, '/') || str_starts_with($path, '//') || 1 === preg_match('/\A[A-Za-z]:/D', $path) || 1 === preg_match('/[\x00-\x1F\x7F]/D', $path) || str_contains($path, ':')) {
            throw new \RuntimeException('noncanonical_relative_path');
        }
        $segments = explode('/', $path);
        foreach ($segments as $segment) {
            if ('' === $segment || '.' === $segment || '..' === $segment) {
                throw new \RuntimeException('ambiguous_relative_path');
            }
        }

        return $path;
    }

    private static function absolute(string $path): string
    {
        $normalized = self::normalize($path);
        $windowsAbsolute = 1 === preg_match('/\A[A-Za-z]:\//D', $normalized);
        $absolute = $windowsAbsolute || str_starts_with($normalized, '/');
        $withoutDrive = $windowsAbsolute ? substr($normalized, 2) : $normalized;
        if ('' === $path || $path !== $normalized || !$absolute || 1 === preg_match('/[\x00-\x1F\x7F]/D', $path) || str_contains($withoutDrive, ':') || str_starts_with($normalized, '//')) {
            throw new \RuntimeException('noncanonical_path');
        }
        $segments = explode('/', trim($withoutDrive, '/'));
        if ([''] === $segments) {
            throw new \RuntimeException('root_substitution');
        }
        foreach ($segments as $segment) {
            if ('' === $segment || '.' === $segment || '..' === $segment) {
                throw new \RuntimeException('ambiguous_path');
            }
        }

        return $normalized;
    }

    private static function containedAncestors(string $root, string $path): void
    {
        $prefix = $root;
        foreach (explode('/', substr($path, strlen($root) + 1)) as $segment) {
            $prefix .= '/' . $segment;
            $real = realpath($prefix);
            $target = @readlink($prefix);
            if (is_link($prefix) || (false !== $real && self::normalize($real) !== $prefix) || (is_string($target) && self::normalize(rtrim($target, '/\\')) !== $prefix)) {
                throw new \RuntimeException('link_or_reparse');
            }
        }
    }

    private static function ancestors(string $path): void
    {
        $drive = 1 === preg_match('/\A[A-Za-z]:\//D', $path) ? substr($path, 0, 2) : '';
        $rest = '' !== $drive ? substr($path, 3) : ltrim($path, '/');
        $prefix = '' !== $drive ? $drive . '/' : '/';
        foreach (explode('/', $rest) as $segment) {
            $prefix = rtrim($prefix, '/') . '/' . $segment;
            $real = realpath($prefix);
            $target = @readlink($prefix);
            if (is_link($prefix) || (false !== $real && self::normalize($real) !== $prefix) || (is_string($target) && self::normalize(rtrim($target, '/\\')) !== $prefix)) {
                throw new \RuntimeException('link_or_reparse');
            }
        }
    }

    private static function normalize(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    private function __construct() {}
}
