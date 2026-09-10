<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceBindingBuild\Artifact;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceBindingBuild\Exception\ExternalServiceBindingArtifactPublicationException;
use Kongono\ExternalServiceBindingBuild\Internal\CanonicalJson;
use Kongono\ExternalServiceBindingBuild\Internal\PathGuard;
use Throwable;

/** Publishes one validated content-addressed binding artifact without overwrite. */
#[Api(ApiStatus::Experimental)]
final class ExternalServiceBindingArtifactPublisher
{
    public function publish(ExternalServiceBindingArtifact $artifact, string $outputDirectory): string
    {
        try {
            $validated = (new ExternalServiceBindingArtifactValidator())->validateStructure($artifact->canonicalBytes());
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw ExternalServiceBindingArtifactPublicationException::failed('invalid_artifact', $failure);
        }
        if (
            !hash_equals($validated->artifactSha256(), $artifact->artifactSha256())
            || !hash_equals($validated->sourceSha256(), $artifact->sourceSha256())
            || !$validated->source()->equals($artifact->source())
        ) {
            throw ExternalServiceBindingArtifactPublicationException::failed('artifact_identity_mismatch');
        }
        $directory = PathGuard::publicationDirectory($outputDirectory);
        if (null === $directory) {
            throw ExternalServiceBindingArtifactPublicationException::failed('invalid_output_directory');
        }
        $target = $directory . '/kongono-external-service-bindings-v1-' . $validated->artifactSha256() . '.json';
        if (false !== @lstat($target)) {
            $this->acceptExisting($target, $validated->canonicalBytes());

            return $target;
        }

        $temporary = $this->temporaryPath($directory, $validated->artifactSha256());
        $handle = @fopen($temporary, 'xb');
        if (false === $handle) {
            throw ExternalServiceBindingArtifactPublicationException::failed('temporary_open_failed');
        }
        $failure = null;
        try {
            if (is_link($temporary) || !is_file($temporary) || !str_starts_with($temporary, $directory . '/')) {
                throw ExternalServiceBindingArtifactPublicationException::failed('unsafe_temporary_file');
            }
            $this->write($handle, $validated->canonicalBytes());
        } catch (Throwable $caught) {
            $failure = $caught;
        } finally {
            if (!@fclose($handle) && null === $failure) {
                $failure = ExternalServiceBindingArtifactPublicationException::failed('temporary_sync_failed');
            }
        }
        if (null === $failure) {
            try {
                if (PathGuard::publicationDirectory($outputDirectory) !== $directory) {
                    throw ExternalServiceBindingArtifactPublicationException::failed('path_changed');
                }
                if (!@link($temporary, $target)) {
                    clearstatcache(true, $target);
                    if (file_exists($target) || is_link($target)) {
                        $this->acceptExisting($target, $validated->canonicalBytes());
                    } else {
                        throw ExternalServiceBindingArtifactPublicationException::failed('atomic_publish_failed');
                    }
                }
                $this->acceptExisting($target, $validated->canonicalBytes());
            } catch (Throwable $caught) {
                $failure = $caught;
            }
        }
        if ((file_exists($temporary) || is_link($temporary)) && !@unlink($temporary)) {
            throw ExternalServiceBindingArtifactPublicationException::failed('temporary_cleanup_failed', $failure);
        }
        if (null !== $failure) {
            throw $failure;
        }

        return $target;
    }

    /** @param resource $handle */
    private function write($handle, string $bytes): void
    {
        $offset = 0;
        while ($offset < strlen($bytes)) {
            $written = @fwrite($handle, substr($bytes, $offset));
            if (false === $written || 0 === $written) {
                throw ExternalServiceBindingArtifactPublicationException::failed('temporary_write_failed');
            }
            $offset += $written;
        }
        if (!@fflush($handle) || (function_exists('fsync') && !@fsync($handle))) {
            throw ExternalServiceBindingArtifactPublicationException::failed('temporary_sync_failed');
        }
        $stat = @fstat($handle);
        if (false === $stat || $stat['size'] !== strlen($bytes)) {
            throw ExternalServiceBindingArtifactPublicationException::failed('temporary_write_failed');
        }
    }

    private function temporaryPath(string $directory, string $sha256): string
    {
        try {
            return $directory . '/.kongono-external-service-bindings-' . $sha256 . '-' . bin2hex(random_bytes(16)) . '.tmp';
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw ExternalServiceBindingArtifactPublicationException::failed('temporary_create_failed', $failure);
        }
    }

    private function acceptExisting(string $target, string $expected): void
    {
        $resolved = realpath($target);
        if (false === $resolved || str_replace('\\', '/', $resolved) !== $target || is_link($target) || !is_file($target)) {
            throw ExternalServiceBindingArtifactPublicationException::failed('unsafe_existing_target');
        }
        $before = @stat($target);
        if (false === $before || $before['size'] > CanonicalJson::MAX_ARTIFACT_BYTES) {
            throw ExternalServiceBindingArtifactPublicationException::failed('existing_target_unreadable');
        }
        $handle = @fopen($target, 'rb');
        if (false === $handle) {
            throw ExternalServiceBindingArtifactPublicationException::failed('existing_target_unreadable');
        }
        try {
            $bytes = stream_get_contents($handle, CanonicalJson::MAX_ARTIFACT_BYTES + 1);
            $after = fstat($handle);
        } finally {
            fclose($handle);
        }
        if (false === $bytes || false === $after || !$this->sameMetadata($before, $after) || strlen($bytes) !== $before['size']) {
            throw ExternalServiceBindingArtifactPublicationException::failed('existing_target_changed');
        }
        if (!hash_equals($expected, $bytes)) {
            throw ExternalServiceBindingArtifactPublicationException::failed('existing_target_conflict');
        }
        try {
            (new ExternalServiceBindingArtifactValidator())->validateStructure($bytes);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw ExternalServiceBindingArtifactPublicationException::failed('existing_target_invalid', $failure);
        }
    }

    /** @param array<string|int, mixed> $before
     * @param array<string|int, mixed> $after
     */
    private function sameMetadata(array $before, array $after): bool
    {
        foreach (['dev', 'ino', 'mode', 'size', 'mtime', 'ctime'] as $key) {
            if (($before[$key] ?? null) !== ($after[$key] ?? null)) {
                return false;
            }
        }

        return true;
    }
}
