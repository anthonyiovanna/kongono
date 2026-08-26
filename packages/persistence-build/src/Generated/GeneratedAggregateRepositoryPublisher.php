<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Generated;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\PersistenceBuild\Diagnostic\PersistenceBuildDiagnostic;
use Kongono\PersistenceBuild\Exception\InvalidPersistenceSourceException;
use Kongono\PersistenceBuild\Internal\PathGuard;
use Kongono\PersistenceBuild\Internal\PersistenceBuildFailureCategory;
use Throwable;

/** Publishes validated aggregate repositories atomically under content identity. */
#[Api(ApiStatus::Experimental)]
final class GeneratedAggregateRepositoryPublisher
{
    public function publish(GeneratedAggregateRepositoryArtifact $artifact, string $outputRoot): string
    {
        try {
            $validated = (new GeneratedAggregateRepositoryValidator())->validateStructure($artifact->canonicalBytes());
            if (!hash_equals($validated->generatedFileSha256(), $artifact->generatedFileSha256())) {
                $this->failed();
            }
            $directory = PathGuard::resolveGeneratedOutputDirectory($outputRoot);
            $target = $directory . '/kongono-persistence-repositories-v1-' . $validated->generatedFileSha256() . '.php';
            if (file_exists($target) || is_link($target)) {
                $this->acceptExisting($target, $validated->canonicalBytes());

                return $target;
            }
            $temporary = @tempnam($directory, '.kongono-persistence-repositories-');
            if (false === $temporary) {
                $this->failed();
            }
            $temporary = str_replace('\\', '/', $temporary);
            try {
                if (!str_starts_with($temporary, $directory . '/') || is_link($temporary) || !is_file($temporary)) {
                    $this->failed();
                }
                $this->writeExact($temporary, $validated->canonicalBytes());
                if (!@link($temporary, $target)) {
                    if (file_exists($target)) {
                        $this->acceptExisting($target, $validated->canonicalBytes());
                    } else {
                        $this->failed();
                    }
                }
                $this->acceptExisting($target, $validated->canonicalBytes());
            } finally {
                if (file_exists($temporary) && !@unlink($temporary)) {
                    $this->failed();
                }
            }

            return $target;
        } catch (InvalidPersistenceSourceException $failure) {
            if (PersistenceBuildDiagnostic::AggregateRepositoryPublicationFailed->value === $failure->diagnosticId()) {
                throw $failure;
            }
            $this->failed();
        } catch (Throwable) {
            $this->failed();
        }
    }

    private function writeExact(string $path, string $bytes): void
    {
        $handle = @fopen($path, 'wb');
        if (false === $handle) {
            $this->failed();
        }
        try {
            $offset = 0;
            while ($offset < strlen($bytes)) {
                $written = fwrite($handle, substr($bytes, $offset));
                if (false === $written || 0 === $written) {
                    $this->failed();
                }
                $offset += $written;
            }
            if (!fflush($handle) || function_exists('fsync') && !fsync($handle)) {
                $this->failed();
            }
        } finally {
            fclose($handle);
        }
    }

    private function acceptExisting(string $target, string $expectedBytes): void
    {
        $resolved = realpath($target);
        $bytes = @file_get_contents($target);
        if (false === $resolved || str_replace('\\', '/', $resolved) !== $target || is_link($target) || !is_file($target) || false === $bytes || !hash_equals($expectedBytes, $bytes)) {
            $this->failed();
        }
        (new GeneratedAggregateRepositoryValidator())->validateStructure($bytes);
    }

    private function failed(): never
    {
        throw InvalidPersistenceSourceException::invalid(PersistenceBuildDiagnostic::AggregateRepositoryPublicationFailed, PersistenceBuildFailureCategory::AggregateRepositoryPublication);
    }
}
