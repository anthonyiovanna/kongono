<?php

declare(strict_types=1);

namespace Kongono\ExtensionBuild\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionBuild\Exception\ExtensionDeclarationPublicationException;

/** Admits one canonical existing ordinary publication directory and its ancestors. */
#[Api(ApiStatus::Internal)]
final class PathGuard
{
    public static function publicationDirectory(string $path): string
    {
        $canonical = self::canonical($path);
        $resolved = realpath($path);
        if (
            false === $resolved
            || self::normalize($resolved) !== $canonical
            || !is_dir($path)
            || is_link($path)
        ) {
            throw ExtensionDeclarationPublicationException::failed('invalid_output_directory', 'path');
        }
        self::ancestors($canonical);

        return rtrim($canonical, '/');
    }

    private static function canonical(string $path): string
    {
        $normalized = self::normalize($path);
        $windowsAbsolute = 1 === preg_match('/\A[A-Za-z]:\//D', $normalized);
        $absolute = $windowsAbsolute || str_starts_with($normalized, '/');
        $withoutDrive = $windowsAbsolute ? substr($normalized, 2) : $normalized;
        if (
            '' === $path
            || !$absolute
            || ($normalized !== $path && str_contains($path, '/'))
            || str_contains($path, "\0")
            || str_contains($withoutDrive, ':')
            || str_starts_with($normalized, '//')
        ) {
            throw ExtensionDeclarationPublicationException::failed('noncanonical_path', 'path');
        }
        foreach (explode('/', trim($withoutDrive, '/')) as $segment) {
            if ('' === $segment || '.' === $segment || '..' === $segment) {
                throw ExtensionDeclarationPublicationException::failed('ambiguous_path', 'path');
            }
        }

        return $normalized;
    }

    private static function ancestors(string $path): void
    {
        $drive = 1 === preg_match('/\A[A-Za-z]:\//D', $path) ? substr($path, 0, 2) : '';
        $rest = '' !== $drive ? substr($path, 3) : ltrim($path, '/');
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
                throw ExtensionDeclarationPublicationException::failed('link_or_reparse', 'path');
            }
        }
    }

    private static function normalize(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    private function __construct() {}
}
