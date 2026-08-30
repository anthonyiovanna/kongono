<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphArtifact\Artifact;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphArtifact\Exception\ApplicationGraphArtifactPublicationException;
use Kongono\ApplicationGraphArtifact\Internal\PathGuard;

/** Publishes one validated content-addressed artifact atomically without overwrite. */
#[Api(ApiStatus::Experimental)]
final class ApplicationGraphArtifactPublisher
{
    public function publish(ApplicationGraphArtifact $artifact, string $outputDirectory): string
    {
        try {
            $validated = (new ApplicationGraphArtifactValidator())->validateStructure($artifact->canonicalBytes());
        } catch (\Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }throw ApplicationGraphArtifactPublicationException::failed('invalid_artifact', 'artifact', $failure);
        }
        if (!hash_equals($validated->artifactSha256(), $artifact->artifactSha256())) {
            throw ApplicationGraphArtifactPublicationException::failed('artifact_identity_mismatch', 'artifact');
        }
        $directory = PathGuard::publicationDirectory($outputDirectory);
        $target = $directory . '/kongono-application-graph-v1-' . $validated->artifactSha256() . '.json';
        if (file_exists($target) || is_link($target)) {
            $this->acceptExisting($target, $validated->canonicalBytes());
            return $target;
        }
        $temporary = @tempnam($directory, '.kongono-application-graph-');
        if (false === $temporary) {
            throw ApplicationGraphArtifactPublicationException::failed('temporary_create_failed', 'temporary');
        }$temporary = str_replace('\\', '/', $temporary);
        $published = false;
        try {
            if (!str_starts_with($temporary, $directory . '/') || is_link($temporary) || !is_file($temporary)) {
                throw ApplicationGraphArtifactPublicationException::failed('unsafe_temporary_file', 'temporary');
            }$handle = @fopen($temporary, 'wb');
            if (false === $handle) {
                throw ApplicationGraphArtifactPublicationException::failed('temporary_open_failed', 'temporary');
            }try {
                $bytes = $validated->canonicalBytes();
                $offset = 0;
                while ($offset < strlen($bytes)) {
                    $written = fwrite($handle, substr($bytes, $offset));
                    if (false === $written || 0 === $written) {
                        throw ApplicationGraphArtifactPublicationException::failed('temporary_write_failed', 'temporary');
                    }$offset += $written;
                }if (!fflush($handle) || function_exists('fsync') && !fsync($handle)) {
                    throw ApplicationGraphArtifactPublicationException::failed('temporary_sync_failed', 'temporary');
                }
            } finally {
                fclose($handle);
            }if (!@link($temporary, $target)) {
                if (file_exists($target)) {
                    $this->acceptExisting($target, $validated->canonicalBytes());
                } else {
                    throw ApplicationGraphArtifactPublicationException::failed('atomic_publish_failed');
                }
            }$published = true;
        } finally {
            if (file_exists($temporary) && !@unlink($temporary) && $published) {
                throw ApplicationGraphArtifactPublicationException::failed('temporary_cleanup_failed', 'temporary');
            }
        }
        return $target;
    }

    private function acceptExisting(string $target, string $expected): void
    {
        $resolved = realpath($target);
        if (false === $resolved || str_replace('\\', '/', $resolved) !== $target || is_link($target) || !is_file($target)) {
            throw ApplicationGraphArtifactPublicationException::failed('unsafe_existing_target');
        }$bytes = @file_get_contents($target);
        if (false === $bytes || !hash_equals($expected, $bytes)) {
            throw ApplicationGraphArtifactPublicationException::failed('existing_target_conflict');
        }(new ApplicationGraphArtifactValidator())->validateStructure($bytes);
    }
}
