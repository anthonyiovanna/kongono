<?php

declare(strict_types=1);

namespace Kongono\ObservabilityBuild\Build;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ObservabilityBuild\Diagnostic\ObservabilityBuildDiagnostic;
use Kongono\ObservabilityBuild\Exception\ObservabilityPublicationException;
use Kongono\ObservabilityBuild\Internal\PathGuard;

/** Publishes exact canonical bytes atomically from external temporary state. */
#[Api(ApiStatus::Internal)]
final class ObservabilityArtifactPublisher
{
    public function publish(ObservabilityArtifact $artifact, string $applicationRoot, string $outputDirectory): string
    {
        $validated = (new ObservabilityArtifactValidator())->validateStructure($artifact->canonicalBytes());
        if (!hash_equals($validated->artifactSha256(), $artifact->artifactSha256())) {
            throw new ObservabilityPublicationException(ObservabilityBuildDiagnostic::PublicationFailed);
        }
        $root = PathGuard::applicationRoot($applicationRoot);
        $output = PathGuard::outputDirectory($root, $outputDirectory);
        $target = $output . '/' . self::basename($artifact);
        if ($this->pathExists($target)) {
            $this->acceptExisting($target, $artifact->canonicalBytes());

            return $target;
        }

        $temporary = @tempnam(sys_get_temp_dir(), '.kongono-observability-');
        if (false === $temporary) {
            throw new ObservabilityPublicationException(ObservabilityBuildDiagnostic::PublicationFailed);
        }
        $temporary = self::normalize($temporary);
        try {
            if (!$this->isSafeExternalTemporary($temporary, $root)) {
                throw new ObservabilityPublicationException(ObservabilityBuildDiagnostic::PublicationFailed);
            }
            $handle = @fopen($temporary, 'wb');
            if (false === $handle) {
                throw new ObservabilityPublicationException(ObservabilityBuildDiagnostic::PublicationFailed);
            }
            try {
                $offset = 0;
                $bytes = $artifact->canonicalBytes();
                while ($offset < strlen($bytes)) {
                    $written = fwrite($handle, substr($bytes, $offset));
                    if (false === $written || 0 === $written) {
                        throw new ObservabilityPublicationException(ObservabilityBuildDiagnostic::PublicationFailed);
                    }
                    $offset += $written;
                }
                if (!fflush($handle) || function_exists('fsync') && !fsync($handle)) {
                    throw new ObservabilityPublicationException(ObservabilityBuildDiagnostic::PublicationFailed);
                }
            } finally {
                fclose($handle);
            }

            if (!@link($temporary, $target)) {
                if ($this->pathExists($target)) {
                    $this->acceptExisting($target, $bytes);
                } else {
                    throw new ObservabilityPublicationException(ObservabilityBuildDiagnostic::PublicationFailed);
                }
            }
        } finally {
            if ($this->pathExists($temporary) && !@unlink($temporary)) {
                throw new ObservabilityPublicationException(ObservabilityBuildDiagnostic::PublicationFailed);
            }
        }

        return $target;
    }

    public static function basename(ObservabilityArtifact $artifact): string
    {
        return 'kongono-observability-v1-' . $artifact->artifactSha256() . '.json';
    }

    private function acceptExisting(string $path, string $expectedBytes): void
    {
        $resolved = realpath($path);
        $stat = @lstat($path);
        $bytes = @file_get_contents($path);
        if (
            false === $resolved
            || self::normalize($resolved) !== $path
            || is_link($path)
            || $this->isReparseAlias($path)
            || !is_file($path)
            || false === $stat
            || !self::regularMode($stat)
            || false === $bytes
            || !hash_equals($expectedBytes, $bytes)
        ) {
            throw new ObservabilityPublicationException(ObservabilityBuildDiagnostic::PublicationFailed);
        }
        (new ObservabilityArtifactValidator())->validateStructure($bytes);
    }

    private function isSafeExternalTemporary(string $path, string $root): bool
    {
        if ($path === $root || str_starts_with($path, $root . '/') || str_contains($path, "\0")) {
            return false;
        }
        $resolved = realpath($path);
        if (
            false === $resolved
            || self::normalize($resolved) !== $path
            || is_link($path)
            || $this->isReparseAlias($path)
            || !is_file($path)
        ) {
            return false;
        }

        $candidate = $path;
        while (true) {
            if (is_link($candidate) || $this->isReparseAlias($candidate)) {
                return false;
            }
            $parent = dirname($candidate);
            if ($parent === $candidate || '.' === $parent) {
                break;
            }
            $candidate = $parent;
        }

        $stat = @lstat($path);

        return false !== $stat && self::regularMode($stat);
    }

    /** @param array<string|int,mixed> $stat */
    private static function regularMode(array $stat): bool
    {
        return isset($stat['mode']) && is_int($stat['mode']) && 0100000 === ($stat['mode'] & 0170000);
    }

    private static function normalize(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    private function isReparseAlias(string $path): bool
    {
        $target = @readlink($path);

        return is_string($target)
            && self::comparisonPath($target) !== self::comparisonPath($path);
    }

    /** @phpstan-impure */
    private function pathExists(string $path): bool
    {
        return false !== @lstat($path);
    }

    private static function comparisonPath(string $path): string
    {
        $normalized = self::normalize($path);

        return strlen($normalized) > 3 ? rtrim($normalized, '/') : $normalized;
    }
}
