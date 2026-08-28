<?php

declare(strict_types=1);

namespace Kongono\ObservabilityBuild\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ObservabilityBuild\Diagnostic\ObservabilityBuildDiagnostic;
use Kongono\ObservabilityBuild\Exception\InvalidObservabilitySourceException;
use Kongono\ObservabilityBuild\Exception\ObservabilityPublicationException;

/** Admits exact ordinary contained paths and stable bounded file snapshots. */
#[Api(ApiStatus::Internal)]
final class PathGuard
{
    public const SOURCE_PATH = 'kongono.observability.json';
    public const MAX_SOURCE_BYTES = 32768;

    public static function applicationRoot(string $root): string
    {
        if (
            '' === $root
            || str_contains($root, "\0")
            || self::hasStreamSyntax($root)
            || self::containsLinkOrReparsePoint($root)
        ) {
            throw new InvalidObservabilitySourceException(ObservabilityBuildDiagnostic::InvalidApplicationRoot);
        }
        $resolved = realpath($root);
        if (false === $resolved || !is_dir($resolved) || !self::hasExactAbsoluteCase($root, $resolved)) {
            throw new InvalidObservabilitySourceException(ObservabilityBuildDiagnostic::InvalidApplicationRoot);
        }

        return rtrim(self::normalize($resolved), '/');
    }

    public static function outputDirectory(string $root, string $directory): string
    {
        if (
            '' === $directory
            || str_contains($directory, "\0")
            || self::hasStreamSyntax($directory)
            || self::containsLinkOrReparsePoint($directory)
        ) {
            throw new ObservabilityPublicationException(ObservabilityBuildDiagnostic::PublicationFailed);
        }
        $resolved = realpath($directory);
        if (false === $resolved || !is_dir($resolved) || !self::hasExactAbsoluteCase($directory, $resolved)) {
            throw new ObservabilityPublicationException(ObservabilityBuildDiagnostic::PublicationFailed);
        }
        $output = rtrim(self::normalize($resolved), '/');
        if ($output === $root || !str_starts_with($output, $root . '/')) {
            throw new ObservabilityPublicationException(ObservabilityBuildDiagnostic::PublicationFailed);
        }
        self::assertContainedSegments($root, substr($output, strlen($root) + 1));

        return $output;
    }

    /** @return array{absolute:string,path:string,bytes:string,size:int,sha256:string,stat:array<string|int,mixed>} */
    public static function source(string $root): array
    {
        return self::read($root, self::SOURCE_PATH, self::MAX_SOURCE_BYTES);
    }

    /** @return array{absolute:string,path:string,bytes:string,size:int,sha256:string,stat:array<string|int,mixed>} */
    public static function artifact(string $root, string $absolutePath, int $maximumBytes): array
    {
        $normalized = self::normalize($absolutePath);
        if (!str_starts_with($normalized, $root . '/')) {
            throw new InvalidObservabilitySourceException(ObservabilityBuildDiagnostic::UnsafeSource);
        }

        return self::read($root, substr($normalized, strlen($root) + 1), $maximumBytes);
    }

    /** @param array{absolute:string,bytes:string,size:int,sha256:string,stat:array<string|int,mixed>} $snapshot */
    public static function reverify(array $snapshot): void
    {
        $resolved = realpath($snapshot['absolute']);
        $stat = @stat($snapshot['absolute']);
        $bytes = @file_get_contents($snapshot['absolute']);
        if (
            false === $resolved
            || self::normalize($resolved) !== $snapshot['absolute']
            || self::containsLinkOrReparsePoint($snapshot['absolute'])
            || !is_file($snapshot['absolute'])
            || false === $stat
            || !self::sameMetadata($snapshot['stat'], $stat)
            || false === $bytes
            || strlen($bytes) !== $snapshot['size']
            || !hash_equals($snapshot['sha256'], hash('sha256', $bytes))
        ) {
            throw new InvalidObservabilitySourceException(ObservabilityBuildDiagnostic::UnsafeSource);
        }
    }

    /** @return array{absolute:string,path:string,bytes:string,size:int,sha256:string,stat:array<string|int,mixed>} */
    private static function read(string $root, string $relativePath, int $maximumBytes): array
    {
        if (
            '' === $relativePath
            || str_contains($relativePath, "\0")
            || str_contains($relativePath, '\\')
            || self::hasStreamSyntax($relativePath)
        ) {
            throw new InvalidObservabilitySourceException(ObservabilityBuildDiagnostic::UnsafeSource);
        }
        foreach (explode('/', $relativePath) as $segment) {
            if ('' === $segment || '.' === $segment || '..' === $segment) {
                throw new InvalidObservabilitySourceException(ObservabilityBuildDiagnostic::UnsafeSource);
            }
        }

        $candidate = $root . '/' . $relativePath;
        $resolved = realpath($candidate);
        if (
            false === $resolved
            || self::normalize($resolved) !== $candidate
            || self::containsLinkOrReparsePoint($candidate)
            || !is_file($candidate)
        ) {
            throw new InvalidObservabilitySourceException(ObservabilityBuildDiagnostic::UnsafeSource);
        }
        self::assertContainedSegments($root, $relativePath);

        $before = @stat($candidate);
        if (false === $before || $before['size'] > $maximumBytes || !self::regularMode($before)) {
            throw new InvalidObservabilitySourceException(ObservabilityBuildDiagnostic::UnsafeSource);
        }
        $handle = @fopen($candidate, 'rb');
        if (false === $handle) {
            throw new InvalidObservabilitySourceException(ObservabilityBuildDiagnostic::UnsafeSource);
        }
        $bytes = '';
        try {
            while (!feof($handle)) {
                $remaining = $maximumBytes + 1 - strlen($bytes);
                $chunk = fread($handle, max(1, min(8192, $remaining)));
                if (false === $chunk) {
                    throw new InvalidObservabilitySourceException(ObservabilityBuildDiagnostic::UnsafeSource);
                }
                $bytes .= $chunk;
                if (strlen($bytes) > $maximumBytes) {
                    throw new InvalidObservabilitySourceException(ObservabilityBuildDiagnostic::UnsafeSource);
                }
            }
            $after = fstat($handle);
        } finally {
            fclose($handle);
        }
        if (
            false === $after
            || !self::regularMode($after)
            || !self::sameMetadata($before, $after)
            || strlen($bytes) !== $before['size']
        ) {
            throw new InvalidObservabilitySourceException(ObservabilityBuildDiagnostic::UnsafeSource);
        }

        return [
            'absolute' => $candidate,
            'path' => $relativePath,
            'bytes' => $bytes,
            'size' => strlen($bytes),
            'sha256' => hash('sha256', $bytes),
            'stat' => $before,
        ];
    }

    private static function assertContainedSegments(string $root, string $relativePath): void
    {
        $prefix = $root;
        foreach (explode('/', $relativePath) as $segment) {
            $prefix .= '/' . $segment;
            $resolved = realpath($prefix);
            if (
                false === $resolved
                || self::normalize($resolved) !== $prefix
                || self::containsLinkOrReparsePoint($prefix)
            ) {
                throw new InvalidObservabilitySourceException(ObservabilityBuildDiagnostic::UnsafeSource);
            }
        }
    }

    /** @param array<string|int,mixed> $stat */
    private static function regularMode(array $stat): bool
    {
        return isset($stat['mode']) && is_int($stat['mode']) && 0100000 === ($stat['mode'] & 0170000);
    }

    /** @param array<string|int,mixed> $before
     * @param array<string|int,mixed> $after
     */
    private static function sameMetadata(array $before, array $after): bool
    {
        foreach (['dev', 'ino', 'mode', 'size', 'mtime', 'ctime'] as $key) {
            if (($before[$key] ?? null) !== ($after[$key] ?? null)) {
                return false;
            }
        }

        return true;
    }

    private static function containsLinkOrReparsePoint(string $path): bool
    {
        $candidate = rtrim($path, '/\\');
        while ('' !== $candidate) {
            $target = @readlink($candidate);
            if (
                is_link($candidate)
                || (is_string($target) && self::comparisonPath($target) !== self::comparisonPath($candidate))
            ) {
                return true;
            }
            $parent = dirname($candidate);
            if ($parent === $candidate || '.' === $parent) {
                break;
            }
            $candidate = $parent;
        }

        return false;
    }

    private static function hasExactAbsoluteCase(string $supplied, string $resolved): bool
    {
        $normalized = rtrim(self::normalize($supplied), '/');
        if (1 !== preg_match('/^(?:[A-Za-z]:\/|\/)/D', $normalized)) {
            return true;
        }

        return $normalized === rtrim(self::normalize($resolved), '/');
    }

    private static function hasStreamSyntax(string $path): bool
    {
        $normalized = self::normalize($path);
        $withoutDrive = 1 === preg_match('/^[A-Za-z]:\//D', $normalized) ? substr($normalized, 2) : $normalized;

        return str_contains($withoutDrive, ':') || str_starts_with($normalized, '//');
    }

    private static function normalize(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    private static function comparisonPath(string $path): string
    {
        $normalized = self::normalize($path);

        return strlen($normalized) > 3 ? rtrim($normalized, '/') : $normalized;
    }

    private function __construct() {}
}
