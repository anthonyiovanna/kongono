<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Build;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\PersistenceBuild\Exception\PersistenceArtifactPublicationException;
use Kongono\PersistenceBuild\Internal\PathGuard;
use Throwable;

/** Publishes one validated artifact atomically beneath an existing application root. */
#[Api(ApiStatus::Experimental)]
final class PersistenceArtifactPublisher
{
    public function publish(PersistenceArtifact $artifact, string $destinationRoot): string
    {
        try {
            $validated = (new PersistenceArtifactValidator())->validateStructure($artifact->canonicalBytes());
            if (!hash_equals($validated->artifactSha256(), $artifact->artifactSha256())) {
                throw PersistenceArtifactPublicationException::failed();
            }
            $directory = PathGuard::resolvePublicationDirectory($destinationRoot);
            $target = $directory . '/kongono-persistence-v1-' . $validated->artifactSha256() . '.json';
            if (file_exists($target) || is_link($target)) {
                $this->acceptExisting($target, $validated->canonicalBytes());

                return $target;
            }
            $temporary = @tempnam($directory, '.kongono-persistence-');
            if (false === $temporary) {
                throw PersistenceArtifactPublicationException::failed();
            }
            $temporary = str_replace('\\', '/', $temporary);
            $primary = null;
            try {
                if (!str_starts_with($temporary, $directory . '/') || is_link($temporary) || !is_file($temporary)) {
                    throw PersistenceArtifactPublicationException::failed();
                }
                $this->writeExact($temporary, $validated->canonicalBytes());
                if (!@link($temporary, $target)) {
                    if (file_exists($target)) {
                        $this->acceptExisting($target, $validated->canonicalBytes());
                    } else {
                        throw PersistenceArtifactPublicationException::failed();
                    }
                }
                $this->acceptExisting($target, $validated->canonicalBytes());
            } catch (Throwable $failure) {
                $primary = $failure;
                throw $failure;
            } finally {
                if (file_exists($temporary) && !@unlink($temporary)) {
                    throw PersistenceArtifactPublicationException::failed($primary);
                }
            }

            return $target;
        } catch (PersistenceArtifactPublicationException $failure) {
            throw $failure;
        } catch (Throwable $failure) {
            throw PersistenceArtifactPublicationException::failed($failure);
        }
    }

    private function writeExact(string $path, string $bytes): void
    {
        $handle = @fopen($path, 'wb');
        if (false === $handle) {
            throw PersistenceArtifactPublicationException::failed();
        }
        try {
            $offset = 0;
            while ($offset < strlen($bytes)) {
                $written = fwrite($handle, substr($bytes, $offset));
                if (false === $written || 0 === $written) {
                    throw PersistenceArtifactPublicationException::failed();
                }
                $offset += $written;
            }
            if (!fflush($handle) || (function_exists('fsync') && !fsync($handle))) {
                throw PersistenceArtifactPublicationException::failed();
            }
        } finally {
            fclose($handle);
        }
    }

    private function acceptExisting(string $target, string $expectedBytes): void
    {
        $resolved = realpath($target);
        $bytes = @file_get_contents($target);
        if (
            false === $resolved
            || str_replace('\\', '/', $resolved) !== $target
            || is_link($target)
            || !is_file($target)
            || false === $bytes
            || !hash_equals($expectedBytes, $bytes)
        ) {
            throw PersistenceArtifactPublicationException::failed();
        }
        (new PersistenceArtifactValidator())->validateStructure($bytes);
    }
}
