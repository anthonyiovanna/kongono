<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterVite\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetAdapterVite\Exception\ViteWorkspaceException;

/** Owns canonical path, containment, and portable relative-path validation. */
#[Api(ApiStatus::Internal)]final class PathGuard
{
    public static function existingDirectory(string $path, bool $writable = true): string
    {
        $resolved = realpath($path);
        if (false === $resolved || is_link($path) || !is_dir($resolved) || ($writable && !is_writable($resolved))) {
            throw ViteWorkspaceException::failed('parent_invalid');
        }return self::normalize($resolved);
    }public static function existingFile(string $path): string
    {
        $resolved = realpath($path);
        if (false === $resolved || is_link($path) || !is_file($resolved) || self::normalize($resolved) !== self::normalize($path)) {
            throw \Kongono\AssetAdapterVite\Exception\InvalidViteToolchainException::invalid('launch_not_ordinary');
        }return self::normalize($resolved);
    }public static function relative(string $path): string
    {
        if ('' === $path || strlen($path) > 1024 || str_contains($path, '\\') || str_contains($path, ':') || str_starts_with($path, '/') || 1 === preg_match('/[\x00-\x1F\x7F]/', $path)) {
            throw ViteWorkspaceException::failed('path_unsafe');
        }$segments = explode('/', $path);
        foreach ($segments as $segment) {
            if ('' === $segment || '.' === $segment || '..' === $segment || str_ends_with($segment, '.') || str_ends_with($segment, ' ') || 1 === preg_match('/\A(?:CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])(?:\.|\z)/i', $segment)) {
                throw ViteWorkspaceException::failed('path_unsafe');
            }
        }return$path;
    }public static function normalize(string $path): string
    {
        $value = str_replace('\\', '/', $path);
        if (strlen($value) > 3) {
            $value = rtrim($value, '/');
        }return$value;
    }public static function contained(string $root, string $path): bool
    {
        $root = self::normalize($root);
        $path = self::normalize($path);
        return str_starts_with($path, $root . '/');
    }
    private function __construct() {}
}
