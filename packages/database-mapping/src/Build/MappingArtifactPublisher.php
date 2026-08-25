<?php

declare(strict_types=1);

namespace Kongono\DatabaseMapping\Build;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseMapping\Exception\MappingArtifactPublicationException;
use Kongono\DatabaseMapping\Internal\PathGuard;

/** Publishes one validated mapping artifact atomically under its content identity. */
#[Api(ApiStatus::Experimental)]
final class MappingArtifactPublisher
{
    public function publish(MappingArtifact $artifact, string $outputDirectory): string
    {
        $validated = (new MappingArtifactValidator())->validateStructure($artifact->canonicalBytes());
        if (!hash_equals($validated->artifactSha256(), $artifact->artifactSha256())) {
            throw MappingArtifactPublicationException::failed();
        }

        $directory = PathGuard::resolveMappingOutputDirectory($outputDirectory);
        $target = $directory . '/kongono-data-mapping-v1-' . $validated->artifactSha256() . '.json';
        if (file_exists($target) || is_link($target)) {
            $this->acceptExisting($target, $validated->canonicalBytes());

            return $target;
        }

        $temporary = @tempnam($directory, '.kongono-data-mapping-');
        if (false === $temporary) {
            throw MappingArtifactPublicationException::failed();
        }
        $temporary = str_replace('\\', '/', $temporary);
        try {
            if (!str_starts_with($temporary, $directory . '/') || is_link($temporary) || !is_file($temporary)) {
                throw MappingArtifactPublicationException::failed();
            }
            $handle = @fopen($temporary, 'wb');
            if (false === $handle) {
                throw MappingArtifactPublicationException::failed();
            }
            try {
                $bytes = $validated->canonicalBytes();
                $offset = 0;
                while ($offset < strlen($bytes)) {
                    $written = fwrite($handle, substr($bytes, $offset));
                    if (false === $written || 0 === $written) {
                        throw MappingArtifactPublicationException::failed();
                    }
                    $offset += $written;
                }
                if (!fflush($handle) || (function_exists('fsync') && !fsync($handle))) {
                    throw MappingArtifactPublicationException::failed();
                }
            } finally {
                fclose($handle);
            }
            if (!@link($temporary, $target)) {
                if (file_exists($target)) {
                    $this->acceptExisting($target, $validated->canonicalBytes());
                } else {
                    throw MappingArtifactPublicationException::failed();
                }
            }
            $this->acceptExisting($target, $validated->canonicalBytes());
        } finally {
            if (file_exists($temporary) && !@unlink($temporary)) {
                throw MappingArtifactPublicationException::failed();
            }
        }

        return $target;
    }

    private function acceptExisting(string $target, string $expectedBytes): void
    {
        $resolved = realpath($target);
        if (false === $resolved || str_replace('\\', '/', $resolved) !== $target || is_link($target) || !is_file($target)) {
            throw MappingArtifactPublicationException::failed();
        }
        $bytes = @file_get_contents($target);
        if (false === $bytes || !hash_equals($expectedBytes, $bytes)) {
            throw MappingArtifactPublicationException::failed();
        }
        (new MappingArtifactValidator())->validateStructure($bytes);
    }
}
