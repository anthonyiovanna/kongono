<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphArtifact\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphArtifact\Artifact\ApplicationGraphArtifactBuilder;
use Kongono\ApplicationGraphArtifact\Exception\ApplicationGraphArtifactLoadException;
use Kongono\ApplicationGraphArtifact\Exception\ApplicationGraphArtifactPublicationException;

/** Admits canonical explicit paths and performs one bounded stable file read. */
#[Api(ApiStatus::Internal)]
final class PathGuard
{
    public static function publicationDirectory(string $path): string
    {
        $canonical = self::canonical($path, 'publish');
        $resolved = realpath($path);
        if (false === $resolved || self::normalize($resolved) !== $canonical || !is_dir($path) || is_link($path)) {
            throw ApplicationGraphArtifactPublicationException::failed('invalid_output_directory', 'path');
        }
        self::ancestors($canonical, 'publish');
        return rtrim($canonical, '/');
    }

    /** @return array{absolute:string,bytes:string,stat:array<string|int,mixed>} */
    public static function readArtifact(string $path): array
    {
        $canonical = self::canonical($path, 'load');
        $resolved = realpath($path);
        if (false === $resolved || self::normalize($resolved) !== $canonical || !is_file($path) || is_link($path)) {
            throw ApplicationGraphArtifactLoadException::failed('invalid_artifact_path');
        }
        self::ancestors($canonical, 'load');
        $before = @stat($canonical);
        if (false === $before) {
            throw ApplicationGraphArtifactLoadException::failed('file_unreadable');
        }
        if ($before['size'] > ApplicationGraphArtifactBuilder::MAX_ARTIFACT_BYTES) {
            throw ApplicationGraphArtifactLoadException::failed('artifact_limit', 'artifact');
        }
        $handle = @fopen($canonical, 'rb');
        if (false === $handle) {
            throw ApplicationGraphArtifactLoadException::failed('file_unreadable');
        }
        $bytes = '';
        try {
            while (!feof($handle)) {
                $chunk = fread($handle, max(1, min(8192, ApplicationGraphArtifactBuilder::MAX_ARTIFACT_BYTES + 1 - strlen($bytes))));
                if (false === $chunk) {
                    throw ApplicationGraphArtifactLoadException::failed('file_read_failed');
                }$bytes .= $chunk;
                if (strlen($bytes) > ApplicationGraphArtifactBuilder::MAX_ARTIFACT_BYTES) {
                    throw ApplicationGraphArtifactLoadException::failed('artifact_limit', 'artifact');
                }
            }$after = fstat($handle);
        } finally {
            fclose($handle);
        }
        if (false === $after || !self::same($before, $after) || strlen($bytes) !== $before['size']) {
            throw ApplicationGraphArtifactLoadException::failed('changed_during_read');
        }
        $resolvedAfter = realpath($canonical);
        if (false === $resolvedAfter || self::normalize($resolvedAfter) !== $canonical || is_link($canonical) || !is_file($canonical)) {
            throw ApplicationGraphArtifactLoadException::failed('changed_during_read');
        }
        self::ancestors($canonical, 'load');
        return ['absolute' => $canonical,'bytes' => $bytes,'stat' => $before];
    }

    private static function canonical(string $path, string $operation): string
    {
        $normalized = self::normalize($path);
        $absolute = 1 === preg_match('/\A[A-Za-z]:\//D', $normalized) || str_starts_with($normalized, '/');
        $withoutDrive = 1 === preg_match('/\A[A-Za-z]:\//D', $normalized) ? substr($normalized, 2) : $normalized;
        if ('' === $path || !$absolute || $normalized !== $path && str_contains($path, '/') || str_contains($path, "\0") || str_contains($withoutDrive, ':') || str_starts_with($normalized, '//')) {
            self::pathFailure($operation, 'noncanonical_path');
        }
        foreach (explode('/', trim($withoutDrive, '/')) as $segment) {
            if ('' === $segment || '.' === $segment || '..' === $segment) {
                self::pathFailure($operation, 'ambiguous_path');
            }
        }
        return $normalized;
    }

    private static function ancestors(string $path, string $operation): void
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
                self::pathFailure($operation, 'link_or_reparse');
            }
        }
    }

    /**
     * @param array<string|int,mixed> $before
     * @param array<string|int,mixed> $after
     */
    private static function same(array $before, array $after): bool
    {
        foreach (['dev','ino','mode','size','mtime','ctime'] as $key) {
            if (($before[$key] ?? null) !== ($after[$key] ?? null)) {
                return false;
            }
        }return true;
    }
    private static function normalize(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
    private static function pathFailure(string $operation, string $violation): never
    {
        if ('publish' === $operation) {
            throw ApplicationGraphArtifactPublicationException::failed($violation, 'path');
        }throw ApplicationGraphArtifactLoadException::failed($violation);
    }
    private function __construct() {}
}
