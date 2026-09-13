<?php

declare(strict_types=1);

namespace Kongono\ViewBuild\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewBuild\Exception\ViewArtifactPublicationException;

/** Admits one explicit existing canonical ordinary directory and every ancestor. */
#[Api(ApiStatus::Internal)]
final class PathGuard
{
    public static function publicationDirectory(string $path): string
    {
        $normalized = self::normalize($path);
        $windows = 1 === preg_match('/\A[A-Za-z]:\//D', $normalized);
        if ('' === $path || (!$windows && !str_starts_with($normalized, '/')) || str_starts_with($normalized, '//') || str_contains($path, "\0") || str_contains($path, '/') && str_contains($path, '\\')) {
            throw ViewArtifactPublicationException::failed('invalid_output_directory');
        }
        $withoutRoot = $windows ? substr($normalized, 3) : ltrim($normalized, '/');
        if ('' === $withoutRoot || str_contains($withoutRoot, ':')) {
            throw ViewArtifactPublicationException::failed('invalid_output_directory');
        }
        foreach (explode('/', $withoutRoot) as $segment) {
            if ('' === $segment || '.' === $segment || '..' === $segment) {
                throw ViewArtifactPublicationException::failed('invalid_output_directory');
            }
        }
        $resolved = realpath($path);
        if (false === $resolved || self::normalize($resolved) !== $normalized || !is_dir($path) || !is_writable($path) || is_link($path)) {
            throw ViewArtifactPublicationException::failed('invalid_output_directory');
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
            $linkTarget = @readlink($current);
            $stat = @lstat($current);
            if (false === $stat
                || 0040000 !== ($stat['mode'] & 0170000)
                || is_link($current)
                || is_string($linkTarget) && self::normalize(rtrim($linkTarget, '/\\')) !== $current
                || false !== $resolved && self::normalize($resolved) !== $current) {
                throw ViewArtifactPublicationException::failed('invalid_output_directory');
            }
        }
    }

    private static function normalize(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);
        if (1 === preg_match('/\A[A-Z]:\//D', $normalized)) {
            $normalized = strtolower($normalized[0]) . substr($normalized, 1);
        }

        return $normalized;
    }

    private function __construct() {}
}
