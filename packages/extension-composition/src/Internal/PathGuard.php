<?php

declare(strict_types=1);

namespace Kongono\ExtensionComposition\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionComposition\Exception\ExtensionWiringPublicationException;

/** Enforces exact ordinary contained paths, stable reads, and reparse-safe publication roots. */
#[Api(ApiStatus::Internal)]
final class PathGuard
{
    public static function readImplementationFile(string $root, string $relative): string
    {
        $root = self::exactDirectory($root, false);
        if ('' === $relative || str_contains($relative, "\0") || str_contains($relative, '\\') || self::hasStream($relative)) {
            throw ExtensionWiringPublicationException::failed('invalid_artifact');
        }
        foreach (explode('/', $relative) as $segment) {
            if ('' === $segment || '.' === $segment || '..' === $segment) {
                throw ExtensionWiringPublicationException::failed('invalid_artifact');
            }
        }
        $candidate = $root . '/' . $relative;
        $resolved = realpath($candidate);
        if (false === $resolved || self::normalize($resolved) !== $candidate || is_link($candidate) || !is_file($candidate)) {
            throw ExtensionWiringPublicationException::failed('invalid_artifact');
        }
        self::assertNoAliasChain($root, $relative, 'invalid_artifact');
        $bytes = @file_get_contents($candidate);
        if (false === $bytes) {
            throw ExtensionWiringPublicationException::failed('invalid_artifact');
        }

        return $bytes;
    }

    public static function publicationDirectory(string $directory): string
    {
        return self::exactDirectory($directory, true);
    }

    private static function exactDirectory(string $directory, bool $publication): string
    {
        if ('' === $directory || str_contains($directory, "\0") || self::hasStream($directory)) {
            throw ExtensionWiringPublicationException::failed($publication ? 'invalid_output_directory' : 'invalid_artifact');
        }
        $resolved = realpath($directory);
        if (false === $resolved || !is_dir($resolved) || is_link($directory)) {
            throw ExtensionWiringPublicationException::failed($publication ? 'invalid_output_directory' : 'invalid_artifact');
        }
        $normalized = rtrim(self::normalize($resolved), '/');
        if (rtrim(self::normalize($directory), '/') !== $normalized) {
            throw ExtensionWiringPublicationException::failed($publication ? 'invalid_output_directory' : 'invalid_artifact');
        }
        self::assertNoDirectoryAliasChain($normalized, $publication ? 'invalid_output_directory' : 'invalid_artifact');

        return $normalized;
    }

    private static function assertNoDirectoryAliasChain(string $path, string $failure): void
    {
        $drive = 1 === preg_match('/\A[A-Za-z]:\//D', $path) ? substr($path, 0, 2) : '';
        $rest = '' !== $drive ? substr($path, 3) : ltrim($path, '/');
        $prefix = '' !== $drive ? $drive . '/' : '/';
        foreach (explode('/', $rest) as $segment) {
            $prefix = rtrim($prefix, '/') . '/' . $segment;
            $linkTarget = @readlink($prefix);
            $resolved = realpath($prefix);
            if (is_link($prefix) || (is_string($linkTarget) && self::normalize(rtrim($linkTarget, '/\\')) !== $prefix) || (false !== $resolved && self::normalize($resolved) !== $prefix)) {
                throw ExtensionWiringPublicationException::failed($failure);
            }
        }
    }

    private static function assertNoAliasChain(string $root, string $relative, string $failure): void
    {
        $prefix = $root;
        foreach (explode('/', $relative) as $segment) {
            $prefix .= '/' . $segment;
            $resolved = realpath($prefix);
            if (false === $resolved || self::normalize($resolved) !== $prefix || is_link($prefix)) {
                throw ExtensionWiringPublicationException::failed($failure);
            }
        }
    }

    private static function hasStream(string $path): bool
    {
        $normalized = self::normalize($path);
        $withoutDrive = 1 === preg_match('/\A[A-Za-z]:\//D', $normalized) ? substr($normalized, 2) : $normalized;

        return str_contains($withoutDrive, ':') || str_starts_with($normalized, '//');
    }

    private static function normalize(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
    private function __construct() {}
}
