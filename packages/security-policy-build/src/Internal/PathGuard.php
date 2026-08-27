<?php

declare(strict_types=1);

namespace Kongono\SecurityPolicyBuild\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\SecurityPolicyBuild\Diagnostic\SecurityPolicyBuildDiagnostic;
use Kongono\SecurityPolicyBuild\Exception\InvalidSecurityPolicySourceException;
use Kongono\SecurityPolicyBuild\Exception\SecurityPolicyPublicationException;

/** Admits exact ordinary contained paths and stable bounded file snapshots. */
#[Api(ApiStatus::Internal)]
final class PathGuard
{
    public const SOURCE_PATH = 'kongono.security-policy.json';
    public const MAX_SOURCE_BYTES = 32768;

    public static function applicationRoot(string $root): string
    {
        if ('' === $root || str_contains($root, "\0") || self::hasStreamSyntax($root) || self::containsLink($root)) {
            throw new InvalidSecurityPolicySourceException(SecurityPolicyBuildDiagnostic::InvalidApplicationRoot);
        }
        $resolved = realpath($root);
        if (false === $resolved || !is_dir($resolved)) {
            throw new InvalidSecurityPolicySourceException(SecurityPolicyBuildDiagnostic::InvalidApplicationRoot);
        }

        return rtrim(self::normalize($resolved), '/');
    }

    public static function outputDirectory(string $root, string $directory): string
    {
        if ('' === $directory || str_contains($directory, "\0") || self::hasStreamSyntax($directory) || self::containsLink($directory)) {
            throw new SecurityPolicyPublicationException(SecurityPolicyBuildDiagnostic::PublicationFailed);
        }
        $resolved = realpath($directory);
        if (false === $resolved || !is_dir($resolved)) {
            throw new SecurityPolicyPublicationException(SecurityPolicyBuildDiagnostic::PublicationFailed);
        }
        $output = rtrim(self::normalize($resolved), '/');
        if ($output === $root || !str_starts_with($output, $root . '/')) {
            throw new SecurityPolicyPublicationException(SecurityPolicyBuildDiagnostic::PublicationFailed);
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
            throw new InvalidSecurityPolicySourceException(SecurityPolicyBuildDiagnostic::UnsafeSource);
        }

        return self::read($root, substr($normalized, strlen($root) + 1), $maximumBytes);
    }

    /** @param array{absolute:string,bytes:string,size:int,sha256:string,stat:array<string|int,mixed>} $snapshot */
    public static function reverify(array $snapshot): void
    {
        $stat = @stat($snapshot['absolute']);
        $bytes = @file_get_contents($snapshot['absolute']);
        if (
            false === $stat
            || !self::sameMetadata($snapshot['stat'], $stat)
            || false === $bytes
            || strlen($bytes) !== $snapshot['size']
            || !hash_equals($snapshot['sha256'], hash('sha256', $bytes))
        ) {
            throw new InvalidSecurityPolicySourceException(SecurityPolicyBuildDiagnostic::UnsafeSource);
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
            throw new InvalidSecurityPolicySourceException(SecurityPolicyBuildDiagnostic::UnsafeSource);
        }
        foreach (explode('/', $relativePath) as $segment) {
            if ('' === $segment || '.' === $segment || '..' === $segment) {
                throw new InvalidSecurityPolicySourceException(SecurityPolicyBuildDiagnostic::UnsafeSource);
            }
        }
        $candidate = $root . '/' . $relativePath;
        $resolved = realpath($candidate);
        if (false === $resolved || self::normalize($resolved) !== $candidate || !is_file($candidate)) {
            throw new InvalidSecurityPolicySourceException(SecurityPolicyBuildDiagnostic::UnsafeSource);
        }
        self::assertContainedSegments($root, $relativePath);

        $before = @stat($candidate);
        if (false === $before || $before['size'] > $maximumBytes) {
            throw new InvalidSecurityPolicySourceException(SecurityPolicyBuildDiagnostic::UnsafeSource);
        }
        $handle = @fopen($candidate, 'rb');
        if (false === $handle) {
            throw new InvalidSecurityPolicySourceException(SecurityPolicyBuildDiagnostic::UnsafeSource);
        }
        $bytes = '';
        try {
            while (!feof($handle)) {
                $chunk = fread($handle, max(1, min(8192, $maximumBytes + 1 - strlen($bytes))));
                if (false === $chunk) {
                    throw new InvalidSecurityPolicySourceException(SecurityPolicyBuildDiagnostic::UnsafeSource);
                }
                $bytes .= $chunk;
                if (strlen($bytes) > $maximumBytes) {
                    throw new InvalidSecurityPolicySourceException(SecurityPolicyBuildDiagnostic::UnsafeSource);
                }
            }
            $after = fstat($handle);
        } finally {
            fclose($handle);
        }
        if (false === $after || !self::sameMetadata($before, $after) || strlen($bytes) !== $before['size']) {
            throw new InvalidSecurityPolicySourceException(SecurityPolicyBuildDiagnostic::UnsafeSource);
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
            if (is_link($prefix) || false === $resolved || self::normalize($resolved) !== $prefix) {
                throw new InvalidSecurityPolicySourceException(SecurityPolicyBuildDiagnostic::UnsafeSource);
            }
        }
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

    private static function containsLink(string $path): bool
    {
        $candidate = rtrim($path, '/\\');
        while ('' !== $candidate) {
            $parent = dirname($candidate);
            if ($parent === $candidate || '.' === $parent) {
                break;
            }
            $target = @readlink($candidate);
            if (
                is_link($candidate)
                || (is_string($target) && self::normalize(rtrim($target, '/\\')) !== self::normalize($candidate))
            ) {
                return true;
            }
            $candidate = $parent;
        }

        return false;
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
}
