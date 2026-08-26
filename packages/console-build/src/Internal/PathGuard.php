<?php

declare(strict_types=1);

namespace Kongono\ConsoleBuild\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ConsoleBuild\Diagnostic\ConsoleBuildDiagnostic;
use Kongono\ConsoleBuild\Exception\InvalidConsoleSourceException;

/** Performs contained exact-file admission and stable bounded source reads. */
#[Api(ApiStatus::Internal)]
final class PathGuard
{
    public const SOURCE_BASENAME = 'kongono.commands.json';
    public const MAX_SOURCE_BYTES = 1_048_576;

    public static function resolveApplicationRoot(string $root): string
    {
        if ('' === $root || str_contains($root, "\0") || self::hasStreamSyntax($root)) {
            self::fail(ConsoleBuildFailureCategory::SourcePath);
        }
        $resolved = realpath($root);
        if (false === $resolved || !is_dir($resolved) || is_link($root)) {
            self::fail(ConsoleBuildFailureCategory::SourcePath);
        }

        return rtrim(self::normalize($resolved), '/');
    }

    /** @return array{absolute:string,path:string,bytes:string,size:int,sha256:string,stat:array<string|int, mixed>} */
    public static function readCommandSource(string $root, string $relativePath): array
    {
        if (self::SOURCE_BASENAME !== basename($relativePath)) {
            self::fail(ConsoleBuildFailureCategory::SourcePath);
        }

        return self::read($root, $relativePath);
    }

    public static function validateSourceText(string $bytes): void
    {
        if ('' === $bytes || str_starts_with($bytes, "\xEF\xBB\xBF") || str_contains($bytes, "\0") || 1 !== preg_match('//u', $bytes)) {
            self::fail(ConsoleBuildFailureCategory::SourceBytes);
        }
    }

    /** @param array{absolute:string,path:string,bytes:string,size:int,sha256:string,stat:array<string|int, mixed>} $snapshot */
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
            throw InvalidConsoleSourceException::invalid(ConsoleBuildDiagnostic::InputChanged, ConsoleBuildFailureCategory::ChangedDuringRead);
        }
    }

    /** @return array{absolute:string,path:string,bytes:string,size:int,sha256:string,stat:array<string|int, mixed>} */
    private static function read(string $root, string $relativePath): array
    {
        if (
            '' === $relativePath
            || str_contains($relativePath, "\0")
            || self::hasStreamSyntax($relativePath)
            || str_contains($relativePath, '\\')
            || str_starts_with($relativePath, '/')
        ) {
            self::fail(ConsoleBuildFailureCategory::SourcePath);
        }
        foreach (explode('/', $relativePath) as $segment) {
            if ('' === $segment || '.' === $segment || '..' === $segment) {
                self::fail(ConsoleBuildFailureCategory::SourcePath);
            }
        }
        $candidate = $root . '/' . $relativePath;
        $resolved = realpath($candidate);
        if (false === $resolved || self::normalize($resolved) !== $candidate || !str_starts_with($candidate, $root . '/')) {
            self::fail(ConsoleBuildFailureCategory::SourcePath);
        }
        $prefix = $root;
        foreach (explode('/', $relativePath) as $segment) {
            $prefix .= '/' . $segment;
            $realPrefix = realpath($prefix);
            if (is_link($prefix) || false === $realPrefix || self::normalize($realPrefix) !== $prefix) {
                self::fail(ConsoleBuildFailureCategory::SourcePath);
            }
        }
        if (!is_file($candidate)) {
            self::fail(ConsoleBuildFailureCategory::SourcePath);
        }
        $before = @stat($candidate);
        if (false === $before || $before['size'] < 1 || $before['size'] > self::MAX_SOURCE_BYTES) {
            self::fail(ConsoleBuildFailureCategory::SourceBounds);
        }
        $handle = @fopen($candidate, 'rb');
        if (false === $handle) {
            self::fail(ConsoleBuildFailureCategory::SourcePath);
        }
        $bytes = '';
        try {
            while (!feof($handle)) {
                $chunk = fread($handle, max(1, min(8192, self::MAX_SOURCE_BYTES + 1 - strlen($bytes))));
                if (false === $chunk) {
                    self::fail(ConsoleBuildFailureCategory::SourceBytes);
                }
                $bytes .= $chunk;
                if (strlen($bytes) > self::MAX_SOURCE_BYTES) {
                    self::fail(ConsoleBuildFailureCategory::SourceBounds);
                }
            }
            $after = fstat($handle);
        } finally {
            fclose($handle);
        }
        if (false === $after || !self::sameMetadata($before, $after) || strlen($bytes) !== $before['size']) {
            throw InvalidConsoleSourceException::invalid(ConsoleBuildDiagnostic::InputChanged, ConsoleBuildFailureCategory::ChangedDuringRead);
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

    /**
     * @param array<string|int, mixed> $before
     * @param array<string|int, mixed> $after
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

    private static function normalize(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    private static function hasStreamSyntax(string $path): bool
    {
        $normalized = self::normalize($path);
        $withoutDrive = 1 === preg_match('/^[A-Za-z]:\//D', $normalized) ? substr($normalized, 2) : $normalized;

        return str_contains($withoutDrive, ':') || str_starts_with($normalized, '//');
    }

    private static function fail(ConsoleBuildFailureCategory $category): never
    {
        throw InvalidConsoleSourceException::invalid(ConsoleBuildDiagnostic::InvalidSource, $category);
    }

    private function __construct() {}
}
