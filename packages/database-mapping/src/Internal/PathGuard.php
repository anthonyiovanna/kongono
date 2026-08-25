<?php

declare(strict_types=1);

namespace Kongono\DatabaseMapping\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseMapping\Exception\GeneratedDataAccessPublicationException;
use Kongono\DatabaseMapping\Exception\InvalidMappingSourceException;
use Kongono\DatabaseMapping\Exception\MappingArtifactPublicationException;
use Kongono\DatabaseMapping\Generated\GeneratedDataAccessGenerator;

/** Performs contained exact-file admission and stable bounded reads. */
#[Api(ApiStatus::Internal)]
final class PathGuard
{
    public const SOURCE_BASENAME = 'kongono.data-mapping.json';
    public const MAX_SOURCE_BYTES = 1_048_576;

    public static function resolveApplicationRoot(string $root): string
    {
        if ('' === $root || str_contains($root, "\0") || self::hasStreamSyntax($root)) {
            throw InvalidMappingSourceException::invalid(MappingFailureCategory::SourcePath);
        }
        $resolved = realpath($root);
        if (false === $resolved || !is_dir($resolved) || is_link($root)) {
            throw InvalidMappingSourceException::invalid(MappingFailureCategory::SourcePath);
        }

        return rtrim(self::normalize($resolved), '/');
    }

    /** @return array{absolute:string,path:string,bytes:string,size:int,sha256:string,stat:array<string|int, mixed>} */
    public static function readSource(string $resolvedRoot, string $relativePath): array
    {
        if (self::SOURCE_BASENAME !== basename($relativePath)) {
            throw InvalidMappingSourceException::invalid(MappingFailureCategory::SourcePath);
        }

        return self::read($resolvedRoot, $relativePath, self::MAX_SOURCE_BYTES, MappingFailureCategory::SourcePath);
    }

    /** @return array{absolute:string,path:string,bytes:string,size:int,sha256:string,stat:array<string|int, mixed>} */
    public static function readProjectFile(string $resolvedRoot, string $path): array
    {
        return self::read($resolvedRoot, $path, self::MAX_SOURCE_BYTES, MappingFailureCategory::Provenance);
    }

    /** @return array{absolute:string,path:string,bytes:string,size:int,sha256:string,stat:array<string|int, mixed>} */
    public static function readAbsoluteDeclaringFile(string $resolvedRoot, string $absolutePath): array
    {
        $normalized = self::normalize($absolutePath);
        if (!str_starts_with($normalized, $resolvedRoot . '/')) {
            throw InvalidMappingSourceException::invalid(MappingFailureCategory::Provenance);
        }
        $relative = substr($normalized, strlen($resolvedRoot) + 1);
        if ('' === $relative || self::hasStreamSyntax($relative)) {
            throw InvalidMappingSourceException::invalid(MappingFailureCategory::Provenance);
        }

        return self::read($resolvedRoot, $relative, self::MAX_SOURCE_BYTES, MappingFailureCategory::Provenance);
    }

    /** @return array{absolute:string,path:string,bytes:string,size:int,sha256:string,stat:array<string|int, mixed>} */
    public static function readGeneratedFile(string $resolvedRoot, string $absolutePath): array
    {
        $normalized = self::normalize($absolutePath);
        if (!str_starts_with($normalized, $resolvedRoot . '/')) {
            throw InvalidMappingSourceException::invalid(MappingFailureCategory::GeneratedLoad);
        }
        $relative = substr($normalized, strlen($resolvedRoot) + 1);
        if ('' === $relative || self::hasStreamSyntax($relative)) {
            throw InvalidMappingSourceException::invalid(MappingFailureCategory::GeneratedLoad);
        }

        return self::read($resolvedRoot, $relative, GeneratedDataAccessGenerator::MAX_GENERATED_BYTES, MappingFailureCategory::GeneratedLoad);
    }

    public static function validateSourceText(string $bytes): void
    {
        if (
            '' === $bytes
            || str_starts_with($bytes, "\xEF\xBB\xBF")
            || str_contains($bytes, "\0")
            || 1 !== preg_match('//u', $bytes)
        ) {
            throw InvalidMappingSourceException::invalid(MappingFailureCategory::SourceBytes);
        }
    }

    /** @param array{absolute:string,path:string,bytes:string,size:int,sha256:string,stat:array<string|int, mixed>} $snapshot */
    public static function reverify(array $snapshot): void
    {
        $stat = @stat($snapshot['absolute']);
        if (false === $stat || !self::sameMetadata($snapshot['stat'], $stat)) {
            throw InvalidMappingSourceException::invalid(MappingFailureCategory::Provenance);
        }
        $bytes = @file_get_contents($snapshot['absolute']);
        if (false === $bytes || strlen($bytes) !== $snapshot['size'] || !hash_equals($snapshot['sha256'], hash('sha256', $bytes))) {
            throw InvalidMappingSourceException::invalid(MappingFailureCategory::Provenance);
        }
    }

    public static function resolveOutputDirectory(string $directory): string
    {
        if ('' === $directory || str_contains($directory, "\0") || self::hasStreamSyntax($directory)) {
            throw GeneratedDataAccessPublicationException::failed();
        }
        $resolved = realpath($directory);
        if (false === $resolved || !is_dir($resolved) || is_link($directory)) {
            throw GeneratedDataAccessPublicationException::failed();
        }

        return rtrim(self::normalize($resolved), '/');
    }

    public static function resolveMappingOutputDirectory(string $directory): string
    {
        try {
            return self::resolveOutputDirectory($directory);
        } catch (GeneratedDataAccessPublicationException $failure) {
            throw MappingArtifactPublicationException::failed();
        }
    }

    /**
     * @return array{absolute:string,path:string,bytes:string,size:int,sha256:string,stat:array<string|int, mixed>}
     */
    private static function read(string $resolvedRoot, string $relativePath, int $limit, MappingFailureCategory $category): array
    {
        if (
            '' === $relativePath
            || str_contains($relativePath, "\0")
            || self::hasStreamSyntax($relativePath)
            || str_contains($relativePath, '\\')
            || str_starts_with($relativePath, '/')
        ) {
            throw InvalidMappingSourceException::invalid($category);
        }
        foreach (explode('/', $relativePath) as $segment) {
            if ('' === $segment || '.' === $segment || '..' === $segment) {
                throw InvalidMappingSourceException::invalid($category);
            }
        }

        $candidate = $resolvedRoot . '/' . $relativePath;
        $resolved = realpath($candidate);
        if (false === $resolved) {
            throw InvalidMappingSourceException::invalid($category);
        }
        $normalized = self::normalize($resolved);
        if ($normalized !== $candidate || !str_starts_with($normalized, $resolvedRoot . '/')) {
            throw InvalidMappingSourceException::invalid($category);
        }
        $prefix = $resolvedRoot;
        foreach (explode('/', $relativePath) as $segment) {
            $prefix .= '/' . $segment;
            if (is_link($prefix) || self::normalize((string) realpath($prefix)) !== $prefix) {
                throw InvalidMappingSourceException::invalid($category);
            }
        }
        if (!is_file($candidate)) {
            throw InvalidMappingSourceException::invalid($category);
        }

        $before = @stat($candidate);
        if (false === $before || $before['size'] > $limit) {
            throw InvalidMappingSourceException::invalid($before === false ? $category : MappingFailureCategory::MappingBounds);
        }
        $handle = @fopen($candidate, 'rb');
        if (false === $handle) {
            throw InvalidMappingSourceException::invalid($category);
        }
        $bytes = '';
        try {
            while (!feof($handle)) {
                $chunk = fread($handle, max(1, min(8192, $limit + 1 - strlen($bytes))));
                if (false === $chunk) {
                    throw InvalidMappingSourceException::invalid($category);
                }
                $bytes .= $chunk;
                if (strlen($bytes) > $limit) {
                    throw InvalidMappingSourceException::invalid(MappingFailureCategory::MappingBounds);
                }
            }
            $after = fstat($handle);
        } finally {
            fclose($handle);
        }
        if (false === $after || !self::sameMetadata($before, $after) || strlen($bytes) !== $before['size']) {
            throw InvalidMappingSourceException::invalid(MappingFailureCategory::Provenance);
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

    private function __construct() {}
}
