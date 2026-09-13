<?php

declare(strict_types=1);

namespace Kongono\ViewRuntime\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewRuntime\Exception\InvalidRuntimeArtifactException;

/** Performs bounded stable reads of the two fixed contained artifact files. */
#[Api(ApiStatus::Internal)]
final class StableArtifactReader
{
    public const int MAX_FILE_BYTES = 2_097_152;

    /** @return array{root: string, directory: string, metadata: string, generated: string} */
    public function paths(string $artifactRoot, string $artifactDirectory, string $compiledIdentity): array
    {
        $root = $this->canonicalDirectory($artifactRoot, 'invalid_root');
        $directory = $this->canonicalDirectory($artifactDirectory, 'invalid_directory');
        $expectedName = 'kongono-view-v1-' . $compiledIdentity;
        if (dirname($directory) !== $root || basename($directory) !== $expectedName) {
            throw InvalidRuntimeArtifactException::invalid('wrong_directory_name');
        }

        return [
            'root' => $root,
            'directory' => $directory,
            'metadata' => $directory . '/artifact.json',
            'generated' => $directory . '/compiled.php',
        ];
    }

    /** @return array{bytes: string, stat: array<string, int>} */
    public function read(string $path): array
    {
        $resolved = realpath($path);
        if (false === $resolved || self::normalize($resolved) !== $path || is_link($path)) {
            throw InvalidRuntimeArtifactException::invalid('missing_artifact_file');
        }
        $before = @lstat($path);
        if (false === $before || !is_file($path) || ($before['mode'] & 0170000) !== 0100000 || 1 !== $before['nlink']) {
            throw InvalidRuntimeArtifactException::invalid('artifact_file_type');
        }
        if ($before['size'] < 1 || $before['size'] > self::MAX_FILE_BYTES) {
            throw InvalidRuntimeArtifactException::invalid('artifact_file_bound', self::MAX_FILE_BYTES, $before['size']);
        }
        $handle = @fopen($path, 'rb');
        if (false === $handle || !@flock($handle, LOCK_SH | LOCK_NB)) {
            if (is_resource($handle)) {
                @fclose($handle);
            }
            throw InvalidRuntimeArtifactException::invalid('artifact_file_unreadable');
        }
        $bytes = '';
        $after = false;
        try {
            while (!feof($handle)) {
                $remaining = self::MAX_FILE_BYTES + 1 - strlen($bytes);
                $chunk = @fread($handle, max(1, min(8192, $remaining)));
                if (false === $chunk) {
                    throw InvalidRuntimeArtifactException::invalid('artifact_file_unreadable');
                }
                $bytes .= $chunk;
                if (strlen($bytes) > self::MAX_FILE_BYTES) {
                    throw InvalidRuntimeArtifactException::invalid('artifact_file_bound', self::MAX_FILE_BYTES, strlen($bytes));
                }
            }
            $after = @fstat($handle);
        } finally {
            @flock($handle, LOCK_UN);
            @fclose($handle);
        }
        if (false === $after || strlen($bytes) !== $before['size']) {
            throw InvalidRuntimeArtifactException::invalid('artifact_file_unstable');
        }
        foreach (['dev', 'ino', 'mode', 'nlink', 'size', 'mtime', 'ctime'] as $key) {
            if ($before[$key] !== $after[$key]) {
                throw InvalidRuntimeArtifactException::invalid('artifact_file_unstable');
            }
        }

        /** @var array<string, int> $after */
        return ['bytes' => $bytes, 'stat' => $after];
    }

    private function canonicalDirectory(string $path, string $violation): string
    {
        if ('' === $path || str_contains($path, '\\') || str_ends_with($path, '/') || str_contains($path, '/./') || str_contains($path, '/../') || str_ends_with($path, '/.') || str_ends_with($path, '/..') || 1 !== preg_match('#\A(?:[a-z]:/|/)[^\0]*\z#D', $path)) {
            throw InvalidRuntimeArtifactException::invalid($violation);
        }
        $resolved = realpath($path);
        if (false === $resolved || self::normalize($resolved) !== $path || is_link($path) || !is_dir($path)) {
            throw InvalidRuntimeArtifactException::invalid($violation);
        }
        $stat = @lstat($path);
        if (false === $stat || ($stat['mode'] & 0170000) !== 0040000) {
            throw InvalidRuntimeArtifactException::invalid('unsafe_path');
        }

        return $path;
    }

    private static function normalize(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);
        if (1 === preg_match('/\A[A-Z]:\//D', $normalized)) {
            $normalized = strtolower($normalized[0]) . substr($normalized, 1);
        }

        return $normalized;
    }
}
