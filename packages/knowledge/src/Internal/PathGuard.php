<?php

declare(strict_types=1);

namespace Kongono\Knowledge\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Knowledge\Diagnostic\KnowledgeDiagnostic;
use Kongono\Knowledge\Exception\InvalidKnowledgeSourceException;

/** Performs deterministic containment, alias, metadata, and bounded-read checks. */
#[Api(ApiStatus::Internal)]
final class PathGuard
{
    public const MAX_PATH_BYTES = 240;
    public const MAX_SOURCE_BYTES = 524288;
    public const MAX_TOTAL_SOURCE_BYTES = 2097152;

    public static function resolveRoot(string $root): string
    {
        if ('' === $root || str_contains($root, "\0")) {
            throw new InvalidKnowledgeSourceException(KnowledgeDiagnostic::SourceUnavailable, 'invalid_root');
        }

        $resolved = realpath($root);

        if (false === $resolved || !is_dir($resolved) || is_link($resolved)) {
            throw new InvalidKnowledgeSourceException(KnowledgeDiagnostic::SourceUnavailable, 'invalid_root');
        }

        return rtrim(self::normalize($resolved), '/');
    }

    public static function admit(string $path): void
    {
        if ('' === $path || strlen($path) > self::MAX_PATH_BYTES) {
            throw new InvalidKnowledgeSourceException(
                strlen($path) > self::MAX_PATH_BYTES ? KnowledgeDiagnostic::SourceLimitExceeded : KnowledgeDiagnostic::InvalidSourcePath,
                strlen($path) > self::MAX_PATH_BYTES ? 'path_bytes' : 'invalid_path_grammar',
            );
        }

        if (1 !== preg_match('/^(?:[A-Za-z0-9._-]+)(?:\/[A-Za-z0-9._-]+)*$/D', $path)) {
            throw new InvalidKnowledgeSourceException(KnowledgeDiagnostic::InvalidSourcePath, 'invalid_path_grammar');
        }

        foreach (explode('/', $path) as $segment) {
            if ('.' === $segment || '..' === $segment) {
                throw new InvalidKnowledgeSourceException(KnowledgeDiagnostic::InvalidSourcePath, 'path_escape');
            }
        }
    }

    public static function read(string $resolvedRoot, string $path, int $limit = self::MAX_SOURCE_BYTES): string
    {
        self::admit($path);
        $candidate = $resolvedRoot . '/' . $path;
        $resolved = realpath($candidate);

        if (false === $resolved) {
            throw new InvalidKnowledgeSourceException(KnowledgeDiagnostic::SourceUnavailable, 'source_missing');
        }

        $normalized = self::normalize($resolved);
        if ($normalized !== $candidate || !str_starts_with($normalized, $resolvedRoot . '/')) {
            throw new InvalidKnowledgeSourceException(KnowledgeDiagnostic::InvalidSourcePath, 'path_alias');
        }

        $prefix = $resolvedRoot;
        foreach (explode('/', $path) as $segment) {
            $prefix .= '/' . $segment;
            if (is_link($prefix) || self::normalize((string) realpath($prefix)) !== $prefix) {
                throw new InvalidKnowledgeSourceException(KnowledgeDiagnostic::InvalidSourcePath, 'link_or_reparse');
            }
        }

        if (!is_file($candidate)) {
            throw new InvalidKnowledgeSourceException(KnowledgeDiagnostic::UnsafeSource, 'non_regular_source');
        }

        $before = @stat($candidate);
        if (false === $before) {
            throw new InvalidKnowledgeSourceException(KnowledgeDiagnostic::SourceUnavailable, 'source_unreadable');
        }
        if ($before['size'] > $limit) {
            throw new InvalidKnowledgeSourceException(KnowledgeDiagnostic::SourceLimitExceeded, 'source_bytes');
        }

        $handle = @fopen($candidate, 'rb');
        if (false === $handle) {
            throw new InvalidKnowledgeSourceException(KnowledgeDiagnostic::SourceUnavailable, 'source_unreadable');
        }

        $bytes = '';
        try {
            while (!feof($handle)) {
                $chunk = fread($handle, max(1, min(8192, $limit + 1 - strlen($bytes))));
                if (false === $chunk) {
                    throw new InvalidKnowledgeSourceException(KnowledgeDiagnostic::SourceUnavailable, 'source_read_failed');
                }
                $bytes .= $chunk;
                if (strlen($bytes) > $limit) {
                    throw new InvalidKnowledgeSourceException(KnowledgeDiagnostic::SourceLimitExceeded, 'source_bytes');
                }
            }
            $after = fstat($handle);
        } finally {
            fclose($handle);
        }

        if (false === $after || !self::sameMetadata($before, $after) || strlen($bytes) !== $before['size']) {
            throw new InvalidKnowledgeSourceException(KnowledgeDiagnostic::SourceUnavailable, 'source_changed_during_read');
        }

        self::validateText($bytes);

        return $bytes;
    }

    public static function validateText(string $bytes): void
    {
        if (str_starts_with($bytes, "\xEF\xBB\xBF")) {
            throw new InvalidKnowledgeSourceException(KnowledgeDiagnostic::InvalidSourceEncoding, 'utf8_bom');
        }
        if (str_contains($bytes, "\r")) {
            throw new InvalidKnowledgeSourceException(KnowledgeDiagnostic::InvalidSourceEncoding, 'cr_byte');
        }
        if (str_contains($bytes, "\0")) {
            throw new InvalidKnowledgeSourceException(KnowledgeDiagnostic::InvalidSourceEncoding, 'nul_byte');
        }
        if (1 !== preg_match('//u', $bytes)) {
            throw new InvalidKnowledgeSourceException(KnowledgeDiagnostic::InvalidSourceEncoding, 'invalid_utf8');
        }
        if (!str_ends_with($bytes, "\n")) {
            throw new InvalidKnowledgeSourceException(KnowledgeDiagnostic::InvalidSourceEncoding, 'missing_final_lf');
        }
        if (str_ends_with($bytes, "\n\n")) {
            throw new InvalidKnowledgeSourceException(KnowledgeDiagnostic::InvalidSourceEncoding, 'multiple_final_lf');
        }
    }

    /**
     * @param array<string|int, mixed> $before
     * @param array<string|int, mixed> $after
     */
    public static function sameMetadata(array $before, array $after): bool
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
}
