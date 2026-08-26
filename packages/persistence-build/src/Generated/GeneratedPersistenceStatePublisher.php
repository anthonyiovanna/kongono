<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Generated;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\PersistenceBuild\Exception\GeneratedPersistenceStatePublicationException;
use Kongono\PersistenceBuild\Internal\PathGuard;
use Throwable;

/** Publishes validated generated-state PHP atomically under its content identity. */
#[Api(ApiStatus::Experimental)]
final class GeneratedPersistenceStatePublisher
{
    public function publish(GeneratedPersistenceStateArtifact $artifact, string $outputRoot): string
    {
        try {
            $validated = (new GeneratedPersistenceStateValidator())->validateStructure($artifact->canonicalBytes());
            if (!hash_equals($validated->generatedFileSha256(), $artifact->generatedFileSha256())) {
                throw GeneratedPersistenceStatePublicationException::failed();
            }
            $directory = PathGuard::resolveGeneratedOutputDirectory($outputRoot);
            $target = $directory . '/kongono-persistence-state-v1-' . $validated->generatedFileSha256() . '.php';
            if (file_exists($target) || is_link($target)) {
                $this->acceptExisting($target, $validated->canonicalBytes());

                return $target;
            }
            $temporary = @tempnam($directory, '.kongono-persistence-state-');
            if (false === $temporary) {
                throw GeneratedPersistenceStatePublicationException::failed();
            }
            $temporary = str_replace('\\', '/', $temporary);
            $primary = null;
            try {
                if (!str_starts_with($temporary, $directory . '/') || is_link($temporary) || !is_file($temporary)) {
                    throw GeneratedPersistenceStatePublicationException::failed();
                }
                $this->writeExact($temporary, $validated->canonicalBytes());
                if (!@link($temporary, $target)) {
                    if (file_exists($target)) {
                        $this->acceptExisting($target, $validated->canonicalBytes());
                    } else {
                        throw GeneratedPersistenceStatePublicationException::failed();
                    }
                }
                $this->acceptExisting($target, $validated->canonicalBytes());
            } catch (Throwable $failure) {
                $primary = $failure;
                throw $failure;
            } finally {
                if (file_exists($temporary) && !@unlink($temporary)) {
                    throw GeneratedPersistenceStatePublicationException::failed($primary, new \RuntimeException('Generated-state temporary cleanup failed.'));
                }
            }

            return $target;
        } catch (GeneratedPersistenceStatePublicationException $failure) {
            throw $failure;
        } catch (Throwable $failure) {
            throw GeneratedPersistenceStatePublicationException::failed($failure);
        }
    }

    private function writeExact(string $path, string $bytes): void
    {
        $handle = @fopen($path, 'wb');
        if (false === $handle) {
            throw GeneratedPersistenceStatePublicationException::failed();
        }
        try {
            $offset = 0;
            while ($offset < strlen($bytes)) {
                $written = fwrite($handle, substr($bytes, $offset));
                if (false === $written || 0 === $written) {
                    throw GeneratedPersistenceStatePublicationException::failed();
                }
                $offset += $written;
            }
            if (!fflush($handle) || function_exists('fsync') && !fsync($handle)) {
                throw GeneratedPersistenceStatePublicationException::failed();
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
            throw GeneratedPersistenceStatePublicationException::failed();
        }
        (new GeneratedPersistenceStateValidator())->validateStructure($bytes);
    }
}
