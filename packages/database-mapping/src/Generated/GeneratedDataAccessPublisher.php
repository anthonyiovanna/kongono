<?php

declare(strict_types=1);

namespace Kongono\DatabaseMapping\Generated;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseMapping\Exception\GeneratedDataAccessPublicationException;
use Kongono\DatabaseMapping\Internal\PathGuard;

/** Publishes validated generated PHP atomically under its content identity. */
#[Api(ApiStatus::Experimental)]
final class GeneratedDataAccessPublisher
{
    public function publish(GeneratedDataAccessArtifact $artifact, string $outputDirectory): string
    {
        $validated = (new GeneratedDataAccessValidator())->validateStructure($artifact->canonicalBytes());
        if (!hash_equals($validated->generatedFileSha256(), $artifact->generatedFileSha256())) {
            throw GeneratedDataAccessPublicationException::failed();
        }
        $directory = PathGuard::resolveOutputDirectory($outputDirectory);
        $target = $directory . '/kongono-data-access-v1-' . $validated->generatedFileSha256() . '.php';
        if (file_exists($target) || is_link($target)) {
            $this->acceptExisting($target, $validated->canonicalBytes());

            return $target;
        }

        $temporary = @tempnam($directory, '.kongono-data-access-');
        if (false === $temporary) {
            throw GeneratedDataAccessPublicationException::failed();
        }
        $temporary = str_replace('\\', '/', $temporary);
        try {
            if (!str_starts_with($temporary, $directory . '/') || is_link($temporary) || !is_file($temporary)) {
                throw GeneratedDataAccessPublicationException::failed();
            }
            $handle = @fopen($temporary, 'wb');
            if (false === $handle) {
                throw GeneratedDataAccessPublicationException::failed();
            }
            try {
                $bytes = $validated->canonicalBytes();
                $offset = 0;
                while ($offset < strlen($bytes)) {
                    $written = fwrite($handle, substr($bytes, $offset));
                    if (false === $written || 0 === $written) {
                        throw GeneratedDataAccessPublicationException::failed();
                    }
                    $offset += $written;
                }
                if (!fflush($handle) || function_exists('fsync') && !fsync($handle)) {
                    throw GeneratedDataAccessPublicationException::failed();
                }
            } finally {
                fclose($handle);
            }
            if (!@link($temporary, $target)) {
                if (file_exists($target)) {
                    $this->acceptExisting($target, $validated->canonicalBytes());
                } else {
                    throw GeneratedDataAccessPublicationException::failed();
                }
            }
            $this->acceptExisting($target, $validated->canonicalBytes());
        } finally {
            if (file_exists($temporary) && !@unlink($temporary)) {
                throw GeneratedDataAccessPublicationException::failed();
            }
        }

        return $target;
    }

    private function acceptExisting(string $target, string $expectedBytes): void
    {
        $resolved = realpath($target);
        if (false === $resolved || str_replace('\\', '/', $resolved) !== $target || is_link($target) || !is_file($target)) {
            throw GeneratedDataAccessPublicationException::failed();
        }
        $bytes = @file_get_contents($target);
        if (false === $bytes || !hash_equals($expectedBytes, $bytes)) {
            throw GeneratedDataAccessPublicationException::failed();
        }
        (new GeneratedDataAccessValidator())->validateStructure($bytes);
    }
}
