<?php

declare(strict_types=1);

namespace Kongono\Composition\Build;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Composition\Diagnostic\CompositionDiagnostic;
use Kongono\Composition\Exception\CompositionPublicationException;
use Kongono\Composition\Internal\PathGuard;

/** Publishes validated bytes atomically under their content identity. */
#[Api(ApiStatus::Experimental)]
final class CompositionArtifactPublisher
{
    public function publish(CompositionArtifact $artifact, string $outputDirectory): string
    {
        $validated = (new CompositionArtifactValidator())->validateStructure($artifact->canonicalBytes());
        if (!hash_equals($validated->artifactSha256(), $artifact->artifactSha256())) {
            throw new CompositionPublicationException(CompositionDiagnostic::PublicationFailed, 'artifact_identity_mismatch');
        }
        $directory = PathGuard::resolveOutputDirectory($outputDirectory);
        $basename = 'kongono-composition-v1-' . $validated->artifactSha256() . '.json';
        $target = $directory . '/' . $basename;

        if (file_exists($target) || is_link($target)) {
            $this->acceptExisting($target, $validated->canonicalBytes());

            return $target;
        }

        $temporary = @tempnam($directory, '.kongono-composition-');
        if (false === $temporary) {
            throw new CompositionPublicationException(CompositionDiagnostic::PublicationFailed, 'temporary_create_failed');
        }
        $temporary = str_replace('\\', '/', $temporary);
        $published = false;
        try {
            if (!str_starts_with($temporary, $directory . '/') || is_link($temporary) || !is_file($temporary)) {
                throw new CompositionPublicationException(CompositionDiagnostic::PublicationFailed, 'unsafe_temporary_file');
            }
            $handle = @fopen($temporary, 'wb');
            if (false === $handle) {
                throw new CompositionPublicationException(CompositionDiagnostic::PublicationFailed, 'temporary_open_failed');
            }
            try {
                $bytes = $validated->canonicalBytes();
                $offset = 0;
                while ($offset < strlen($bytes)) {
                    $written = fwrite($handle, substr($bytes, $offset));
                    if (false === $written || 0 === $written) {
                        throw new CompositionPublicationException(CompositionDiagnostic::PublicationFailed, 'temporary_write_failed');
                    }
                    $offset += $written;
                }
                if (!fflush($handle) || function_exists('fsync') && !fsync($handle)) {
                    throw new CompositionPublicationException(CompositionDiagnostic::PublicationFailed, 'temporary_sync_failed');
                }
            } finally {
                fclose($handle);
            }

            if (!@link($temporary, $target)) {
                if (file_exists($target)) {
                    $this->acceptExisting($target, $validated->canonicalBytes());
                } else {
                    throw new CompositionPublicationException(CompositionDiagnostic::PublicationFailed, 'atomic_publish_failed');
                }
            }
            $published = true;
        } finally {
            if (file_exists($temporary) && !@unlink($temporary)) {
                if ($published) {
                    throw new CompositionPublicationException(CompositionDiagnostic::PublicationFailed, 'temporary_cleanup_failed');
                }
            }
        }

        return $target;
    }

    private function acceptExisting(string $target, string $expectedBytes): void
    {
        $resolved = realpath($target);
        if (false === $resolved || str_replace('\\', '/', $resolved) !== $target || is_link($target) || !is_file($target)) {
            throw new CompositionPublicationException(CompositionDiagnostic::PublicationFailed, 'unsafe_existing_target');
        }
        $bytes = @file_get_contents($target);
        if (false === $bytes || !hash_equals($expectedBytes, $bytes)) {
            throw new CompositionPublicationException(CompositionDiagnostic::PublicationFailed, 'existing_target_conflict');
        }
        (new CompositionArtifactValidator())->validateStructure($bytes);
    }
}
