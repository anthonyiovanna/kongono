<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\PersistenceBuild\Diagnostic\PersistenceBuildDiagnostic;
use Kongono\PersistenceBuild\Exception\GeneratedPersistenceStatePublicationException;
use Kongono\PersistenceBuild\Exception\InvalidPersistenceSourceException;
use Kongono\PersistenceBuild\Exception\PersistenceArtifactPublicationException;

/** Performs contained exact-file admission and stable bounded reads. */
#[Api(ApiStatus::Internal)]
final class PathGuard
{
    public const SOURCE_BASENAME = 'kongono.persistence.json';
    public const MAPPING_SOURCE_BASENAME = 'kongono.data-mapping.json';
    public const MAX_SOURCE_BYTES = 1_048_576;

    public static function resolveApplicationRoot(string $root): string
    {
        if ('' === $root || str_contains($root, "\0") || self::hasStreamSyntax($root)) {
            self::sourceFailure(PersistenceBuildFailureCategory::SourcePath);
        }
        $resolved = realpath($root);
        if (false === $resolved || !is_dir($resolved) || is_link($root)) {
            self::sourceFailure(PersistenceBuildFailureCategory::SourcePath);
        }

        return rtrim(self::normalize($resolved), '/');
    }

    /** @return array{absolute:string,path:string,bytes:string,size:int,sha256:string,stat:array<string|int, mixed>} */
    public static function readPersistenceSource(string $root, string $relativePath): array
    {
        if (self::SOURCE_BASENAME !== basename($relativePath)) {
            self::sourceFailure(PersistenceBuildFailureCategory::SourcePath);
        }

        return self::read($root, $relativePath, self::MAX_SOURCE_BYTES, PersistenceBuildFailureCategory::SourcePath);
    }

    /** @return array{absolute:string,path:string,bytes:string,size:int,sha256:string,stat:array<string|int, mixed>} */
    public static function readMappingSource(string $root, string $relativePath): array
    {
        if (self::MAPPING_SOURCE_BASENAME !== basename($relativePath)) {
            self::mappingFailure();
        }

        return self::read($root, $relativePath, self::MAX_SOURCE_BYTES, PersistenceBuildFailureCategory::MappingEvidence);
    }

    /** @return array{absolute:string,path:string,bytes:string,size:int,sha256:string,stat:array<string|int, mixed>} */
    public static function readProjectFile(string $root, string $relativePath): array
    {
        return self::read($root, $relativePath, self::MAX_SOURCE_BYTES, PersistenceBuildFailureCategory::SourcePath);
    }

    /** @return array{absolute:string,path:string,bytes:string,size:int,sha256:string,stat:array<string|int, mixed>} */
    public static function readDomainFile(string $root, string $absolutePath): array
    {
        $normalized = self::normalize($absolutePath);
        if (!str_starts_with($normalized, $root . '/')) {
            self::sourceFailure(PersistenceBuildFailureCategory::Construction, PersistenceBuildDiagnostic::InvalidConstruction);
        }

        return self::read($root, substr($normalized, strlen($root) + 1), self::MAX_SOURCE_BYTES, PersistenceBuildFailureCategory::Construction);
    }

    public static function validateSourceText(string $bytes): void
    {
        if ('' === $bytes || str_starts_with($bytes, "\xEF\xBB\xBF") || str_contains($bytes, "\0") || 1 !== preg_match('//u', $bytes)) {
            self::sourceFailure(PersistenceBuildFailureCategory::SourceBytes);
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
            throw InvalidPersistenceSourceException::invalid(
                PersistenceBuildDiagnostic::InputChanged,
                PersistenceBuildFailureCategory::ChangedDuringRead,
            );
        }
    }

    public static function resolvePublicationDirectory(string $root): string
    {
        if ('' === $root || str_contains($root, "\0") || self::hasStreamSyntax($root)) {
            throw PersistenceArtifactPublicationException::failed();
        }
        $resolved = realpath($root);
        if (false === $resolved || !is_dir($resolved) || is_link($root)) {
            throw PersistenceArtifactPublicationException::failed();
        }
        $root = rtrim(self::normalize($resolved), '/');
        $directory = $root;
        foreach (['persistence-artifacts', 'v1'] as $segment) {
            $directory .= '/' . $segment;
            if (!file_exists($directory) && !@mkdir($directory, 0777)) {
                throw PersistenceArtifactPublicationException::failed();
            }
            $child = realpath($directory);
            if (false === $child || self::normalize($child) !== $directory || !is_dir($directory) || is_link($directory)) {
                throw PersistenceArtifactPublicationException::failed();
            }
        }

        return $directory;
    }

    public static function resolveGeneratedOutputDirectory(string $directory): string
    {
        if ('' === $directory || str_contains($directory, "\0") || self::hasStreamSyntax($directory)) {
            throw GeneratedPersistenceStatePublicationException::failed();
        }
        $resolved = realpath($directory);
        if (false === $resolved || !is_dir($resolved) || is_link($directory)) {
            throw GeneratedPersistenceStatePublicationException::failed();
        }

        return rtrim(self::normalize($resolved), '/');
    }

    /**
     * @return array{absolute:string,path:string,bytes:string,size:int,sha256:string,stat:array<string|int, mixed>}
     */
    private static function read(string $root, string $relativePath, int $limit, PersistenceBuildFailureCategory $category): array
    {
        if (
            '' === $relativePath
            || str_contains($relativePath, "\0")
            || self::hasStreamSyntax($relativePath)
            || str_contains($relativePath, '\\')
            || str_starts_with($relativePath, '/')
        ) {
            self::sourceFailure($category, self::diagnosticFor($category));
        }
        foreach (explode('/', $relativePath) as $segment) {
            if ('' === $segment || '.' === $segment || '..' === $segment) {
                self::sourceFailure($category, self::diagnosticFor($category));
            }
        }
        $candidate = $root . '/' . $relativePath;
        $resolved = realpath($candidate);
        if (false === $resolved || self::normalize($resolved) !== $candidate || !str_starts_with($candidate, $root . '/')) {
            self::sourceFailure($category, self::diagnosticFor($category));
        }
        $prefix = $root;
        foreach (explode('/', $relativePath) as $segment) {
            $prefix .= '/' . $segment;
            $realPrefix = realpath($prefix);
            if (is_link($prefix) || false === $realPrefix || self::normalize($realPrefix) !== $prefix) {
                self::sourceFailure($category, self::diagnosticFor($category));
            }
        }
        if (!is_file($candidate)) {
            self::sourceFailure($category, self::diagnosticFor($category));
        }
        $before = @stat($candidate);
        if (false === $before || $before['size'] < 1 || $before['size'] > $limit) {
            self::sourceFailure(PersistenceBuildFailureCategory::SourceBounds, self::diagnosticFor($category));
        }
        $handle = @fopen($candidate, 'rb');
        if (false === $handle) {
            self::sourceFailure($category, self::diagnosticFor($category));
        }
        $bytes = '';
        try {
            while (!feof($handle)) {
                $chunk = fread($handle, max(1, min(8192, $limit + 1 - strlen($bytes))));
                if (false === $chunk) {
                    self::sourceFailure($category, self::diagnosticFor($category));
                }
                $bytes .= $chunk;
                if (strlen($bytes) > $limit) {
                    self::sourceFailure(PersistenceBuildFailureCategory::SourceBounds, self::diagnosticFor($category));
                }
            }
            $after = fstat($handle);
        } finally {
            fclose($handle);
        }
        if (false === $after || !self::sameMetadata($before, $after) || strlen($bytes) !== $before['size']) {
            throw InvalidPersistenceSourceException::invalid(PersistenceBuildDiagnostic::InputChanged, PersistenceBuildFailureCategory::ChangedDuringRead);
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

    private static function diagnosticFor(PersistenceBuildFailureCategory $category): PersistenceBuildDiagnostic
    {
        return match ($category) {
            PersistenceBuildFailureCategory::MappingEvidence => PersistenceBuildDiagnostic::InvalidMappingEvidence,
            PersistenceBuildFailureCategory::Construction => PersistenceBuildDiagnostic::InvalidConstruction,
            default => PersistenceBuildDiagnostic::InvalidSource,
        };
    }

    private static function sourceFailure(PersistenceBuildFailureCategory $category, PersistenceBuildDiagnostic $diagnostic = PersistenceBuildDiagnostic::InvalidSource): never
    {
        throw InvalidPersistenceSourceException::invalid($diagnostic, $category);
    }

    private static function mappingFailure(): never
    {
        throw InvalidPersistenceSourceException::invalid(PersistenceBuildDiagnostic::InvalidMappingEvidence, PersistenceBuildFailureCategory::MappingEvidence);
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
