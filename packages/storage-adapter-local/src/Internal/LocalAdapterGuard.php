<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterLocal\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageAdapterLocal\Exception\InvalidLocalStorageConfigurationException;

/** Centralizes local root and containment invariants. */
#[Api(ApiStatus::Internal)]
final class LocalAdapterGuard
{
    public static function root(string $path): string
    {
        $normalized = self::normalize($path);
        $windows = 1 === preg_match('/\A[A-Za-z]:\//D', $normalized);
        if ('' === $path || str_contains($path, "\0") || str_contains($path, '://') || str_contains($path, '*') || str_contains($path, '?') || str_starts_with($normalized, '//') || (!$windows && !str_starts_with($normalized, '/')) || (str_contains($path, '/') && str_contains($path, '\\'))) {
            throw InvalidLocalStorageConfigurationException::invalid('root_syntax');
        }
        $rest = $windows ? substr($normalized, 3) : ltrim($normalized, '/');
        if ('' === $rest || str_contains($rest, ':')) {
            throw InvalidLocalStorageConfigurationException::invalid('root_syntax');
        }
        foreach (explode('/', $rest) as $segment) {
            if ('' === $segment || '.' === $segment || '..' === $segment || self::device($segment)) {
                throw InvalidLocalStorageConfigurationException::invalid('root_segment');
            }
        }
        clearstatcache(true, $path);
        $resolved = realpath($path);
        if (false === $resolved || !is_dir($path) || is_link($path)) {
            throw InvalidLocalStorageConfigurationException::invalid('root_type');
        }
        $canonical = rtrim(self::normalize($resolved), '/');
        if ($canonical !== rtrim($normalized, '/')) {
            throw InvalidLocalStorageConfigurationException::invalid('root_resolution');
        }
        self::ordinaryAncestors($canonical);

        return $canonical;
    }

    public static function contained(string $root, string $path, bool $mayBeMissing = false, bool $directory = false): string
    {
        $root = self::root($root);
        $path = self::normalize($path);
        if (!str_starts_with($path, $root . '/')) {
            throw InvalidLocalStorageConfigurationException::invalid('containment');
        }
        $segments = explode('/', substr($path, strlen($root) + 1));
        $current = $root;
        foreach ($segments as $position => $segment) {
            if ('' === $segment || '.' === $segment || '..' === $segment || str_contains($segment, ':') || self::device($segment)) {
                throw InvalidLocalStorageConfigurationException::invalid('containment');
            }
            $current .= '/' . $segment;
            clearstatcache(true, $current);
            $stat = @lstat($current);
            $final = $position === count($segments) - 1;
            if (false === $stat) {
                if ($final && $mayBeMissing && !is_link($current)) {
                    return $path;
                }
                throw InvalidLocalStorageConfigurationException::invalid('missing_path');
            }
            $expectedType = !$final || $directory ? 0040000 : 0100000;
            $resolved = realpath($current);
            if (($stat['mode'] & 0170000) !== $expectedType || is_link($current) || false === $resolved || self::normalize($resolved) !== $current) {
                throw InvalidLocalStorageConfigurationException::invalid('containment');
            }
        }

        return $path;
    }

    public static function normalize(string $path): string
    {
        $value = str_replace('\\', '/', $path);
        if (1 === preg_match('/\A[A-Z]:\//D', $value)) {
            $value = strtolower($value[0]) . substr($value, 1);
        }

        return $value;
    }

    private static function ordinaryAncestors(string $path): void
    {
        $windows = 1 === preg_match('/\A[A-Za-z]:\//D', $path);
        $current = $windows ? substr($path, 0, 2) : '';
        $rest = $windows ? substr($path, 3) : ltrim($path, '/');
        foreach (explode('/', $rest) as $segment) {
            $current .= '/' . $segment;
            clearstatcache(true, $current);
            $stat = @lstat($current);
            if (false === $stat || 0040000 !== ($stat['mode'] & 0170000) || is_link($current)) {
                throw InvalidLocalStorageConfigurationException::invalid('root_ancestor');
            }
        }
    }

    private static function device(string $segment): bool
    {
        $base = strtolower(explode('.', $segment, 2)[0]);

        return in_array($base, ['con','prn','aux','nul','clock$','com1','com2','com3','com4','com5','com6','com7','com8','com9','lpt1','lpt2','lpt3','lpt4','lpt5','lpt6','lpt7','lpt8','lpt9'], true);
    }

    private function __construct() {}
}
