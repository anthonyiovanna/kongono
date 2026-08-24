<?php

declare(strict_types=1);

namespace Kongono\Composition\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Composition\Diagnostic\CompositionDiagnostic;
use Kongono\Composition\Exception\CompositionPublicationException;
use Kongono\Composition\Exception\InvalidCompositionSourceException;
use Kongono\Composition\Generated\GeneratedWiringGenerator;

/** Performs contained exact-file admission and stable bounded reads. */
#[Api(ApiStatus::Internal)]
final class PathGuard
{
    public const SOURCE_PATH = 'kongono.composition.json';
    public const MAX_SOURCE_BYTES = 1048576;

    public static function resolveApplicationRoot(string $root): string
    {
        if ('' === $root || str_contains($root, "\0") || self::hasStreamSyntax($root)) {
            throw new InvalidCompositionSourceException(CompositionDiagnostic::InvalidApplicationRoot, 'invalid_root');
        }
        $resolved = realpath($root);
        if (false === $resolved || !is_dir($resolved) || is_link($root)) {
            throw new InvalidCompositionSourceException(CompositionDiagnostic::InvalidApplicationRoot, 'invalid_root');
        }

        return rtrim(self::normalize($resolved), '/');
    }

    /** @return array{absolute:string,path:string,bytes:string,size:int,sha256:string,stat:array<string|int, mixed>} */
    public static function readSource(string $resolvedRoot): array
    {
        return self::read($resolvedRoot, self::SOURCE_PATH, self::MAX_SOURCE_BYTES, CompositionDiagnostic::UnsafeCompositionSource);
    }

    /** @return array{absolute:string,path:string,bytes:string,size:int,sha256:string,stat:array<string|int, mixed>} */
    public static function readProjectFile(string $resolvedRoot, string $path): array
    {
        return self::read($resolvedRoot, $path, self::MAX_SOURCE_BYTES, CompositionDiagnostic::InvalidProvenance);
    }

    /** @return array{absolute:string,path:string,bytes:string,size:int,sha256:string,stat:array<string|int, mixed>} */
    public static function readAbsoluteDeclaringFile(string $resolvedRoot, string $absolutePath): array
    {
        $normalized = self::normalize($absolutePath);
        if (!str_starts_with($normalized, $resolvedRoot . '/')) {
            throw new InvalidCompositionSourceException(CompositionDiagnostic::InvalidProvenance, 'symbol_source_outside_root');
        }
        $relative = substr($normalized, strlen($resolvedRoot) + 1);
        if ('' === $relative || self::hasStreamSyntax($relative)) {
            throw new InvalidCompositionSourceException(CompositionDiagnostic::InvalidProvenance, 'invalid_symbol_source');
        }

        return self::read($resolvedRoot, $relative, self::MAX_SOURCE_BYTES, CompositionDiagnostic::InvalidProvenance);
    }

    /** @return array{absolute:string,path:string,bytes:string,size:int,sha256:string,stat:array<string|int, mixed>} */
    public static function readGeneratedFile(string $resolvedRoot, string $absolutePath): array
    {
        $normalized = self::normalize($absolutePath);
        if (!str_starts_with($normalized, $resolvedRoot . '/')) {
            throw new InvalidCompositionSourceException(CompositionDiagnostic::GeneratedWiringLoadFailed, 'outside_root');
        }
        $relative = substr($normalized, strlen($resolvedRoot) + 1);
        if ('' === $relative || self::hasStreamSyntax($relative)) {
            throw new InvalidCompositionSourceException(CompositionDiagnostic::GeneratedWiringLoadFailed, 'invalid_generated_path');
        }

        return self::read(
            $resolvedRoot,
            $relative,
            GeneratedWiringGenerator::MAX_GENERATED_BYTES,
            CompositionDiagnostic::GeneratedWiringLoadFailed,
        );
    }

    public static function validateSourceText(string $bytes): void
    {
        if ('' === $bytes) {
            throw new InvalidCompositionSourceException(CompositionDiagnostic::InvalidSourceBytes, 'empty_source');
        }
        if (str_starts_with($bytes, "\xEF\xBB\xBF")) {
            throw new InvalidCompositionSourceException(CompositionDiagnostic::InvalidSourceBytes, 'utf8_bom');
        }
        if (str_contains($bytes, "\r")) {
            throw new InvalidCompositionSourceException(CompositionDiagnostic::InvalidSourceBytes, 'cr_byte');
        }
        if (str_contains($bytes, "\0")) {
            throw new InvalidCompositionSourceException(CompositionDiagnostic::InvalidSourceBytes, 'nul_byte');
        }
        if (1 !== preg_match('//u', $bytes)) {
            throw new InvalidCompositionSourceException(CompositionDiagnostic::InvalidSourceBytes, 'invalid_utf8');
        }
        if (!str_ends_with($bytes, "\n")) {
            throw new InvalidCompositionSourceException(CompositionDiagnostic::InvalidSourceBytes, 'missing_final_lf');
        }
        if (str_ends_with($bytes, "\n\n")) {
            throw new InvalidCompositionSourceException(CompositionDiagnostic::InvalidSourceBytes, 'multiple_final_lf');
        }
    }

    /** @param array{absolute:string,path:string,bytes:string,size:int,sha256:string,stat:array<string|int, mixed>} $snapshot */
    public static function reverify(array $snapshot): void
    {
        $stat = @stat($snapshot['absolute']);
        if (false === $stat || !self::sameMetadata($snapshot['stat'], $stat)) {
            throw new InvalidCompositionSourceException(CompositionDiagnostic::InvalidProvenance, 'changed_during_build');
        }
        $bytes = @file_get_contents($snapshot['absolute']);
        if (false === $bytes || strlen($bytes) !== $snapshot['size'] || !hash_equals($snapshot['sha256'], hash('sha256', $bytes))) {
            throw new InvalidCompositionSourceException(CompositionDiagnostic::InvalidProvenance, 'changed_during_build');
        }
    }

    public static function resolveOutputDirectory(string $directory): string
    {
        if ('' === $directory || str_contains($directory, "\0") || self::hasStreamSyntax($directory)) {
            throw new CompositionPublicationException(CompositionDiagnostic::PublicationFailed, 'invalid_output_directory');
        }
        $resolved = realpath($directory);
        if (false === $resolved || !is_dir($resolved) || is_link($directory)) {
            throw new CompositionPublicationException(CompositionDiagnostic::PublicationFailed, 'invalid_output_directory');
        }

        return rtrim(self::normalize($resolved), '/');
    }

    /**
     * @return array{absolute:string,path:string,bytes:string,size:int,sha256:string,stat:array<string|int, mixed>}
     */
    private static function read(string $resolvedRoot, string $relativePath, int $limit, CompositionDiagnostic $unsafeDiagnostic): array
    {
        if ('' === $relativePath || str_contains($relativePath, "\0") || self::hasStreamSyntax($relativePath) || str_contains($relativePath, '\\')) {
            throw new InvalidCompositionSourceException($unsafeDiagnostic, 'invalid_path');
        }
        foreach (explode('/', $relativePath) as $segment) {
            if ('' === $segment || '.' === $segment || '..' === $segment) {
                throw new InvalidCompositionSourceException($unsafeDiagnostic, 'path_escape');
            }
        }

        $candidate = $resolvedRoot . '/' . $relativePath;
        $resolved = realpath($candidate);
        if (false === $resolved) {
            throw new InvalidCompositionSourceException($unsafeDiagnostic, 'required_file_missing');
        }
        $normalized = self::normalize($resolved);
        if ($normalized !== $candidate || !str_starts_with($normalized, $resolvedRoot . '/')) {
            throw new InvalidCompositionSourceException($unsafeDiagnostic, 'path_alias');
        }
        $prefix = $resolvedRoot;
        foreach (explode('/', $relativePath) as $segment) {
            $prefix .= '/' . $segment;
            if (is_link($prefix) || self::normalize((string) realpath($prefix)) !== $prefix) {
                throw new InvalidCompositionSourceException($unsafeDiagnostic, 'link_or_reparse');
            }
        }
        if (!is_file($candidate)) {
            throw new InvalidCompositionSourceException($unsafeDiagnostic, 'non_regular_file');
        }

        $before = @stat($candidate);
        if (false === $before) {
            throw new InvalidCompositionSourceException($unsafeDiagnostic, 'file_unreadable');
        }
        if ($before['size'] > $limit) {
            throw new InvalidCompositionSourceException(CompositionDiagnostic::CompositionLimitExceeded, 'file_bytes');
        }
        $handle = @fopen($candidate, 'rb');
        if (false === $handle) {
            throw new InvalidCompositionSourceException($unsafeDiagnostic, 'file_unreadable');
        }
        $bytes = '';
        try {
            while (!feof($handle)) {
                $chunk = fread($handle, max(1, min(8192, $limit + 1 - strlen($bytes))));
                if (false === $chunk) {
                    throw new InvalidCompositionSourceException($unsafeDiagnostic, 'file_read_failed');
                }
                $bytes .= $chunk;
                if (strlen($bytes) > $limit) {
                    throw new InvalidCompositionSourceException(CompositionDiagnostic::CompositionLimitExceeded, 'file_bytes');
                }
            }
            $after = fstat($handle);
        } finally {
            fclose($handle);
        }
        if (false === $after || !self::sameMetadata($before, $after) || strlen($bytes) !== $before['size']) {
            throw new InvalidCompositionSourceException($unsafeDiagnostic, 'changed_during_read');
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
}
