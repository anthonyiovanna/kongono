<?php

declare(strict_types=1);

namespace Kongono\Composition\Generated;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Composition\Diagnostic\CompositionDiagnostic;
use Kongono\Composition\Exception\GeneratedWiringPublicationException;

/** Publishes validated generated PHP atomically under its content identity. */
#[Api(ApiStatus::Experimental)]
final class GeneratedWiringPublisher
{
    public function publish(GeneratedWiringArtifact $artifact, string $outputDirectory): string
    {
        $validated = (new GeneratedWiringValidator())->validateStructure($artifact->canonicalBytes());
        if (!hash_equals($validated->wiringSha256(), $artifact->wiringSha256())) {
            $this->fail('wiring_identity_mismatch');
        }
        $directory = $this->directory($outputDirectory);
        $target = $directory . '/kongono-wiring-v1-' . $validated->wiringSha256() . '.php';
        if (file_exists($target) || is_link($target)) {
            $this->acceptExisting($target, $validated->canonicalBytes());
            return $target;
        }

        $temporary = @tempnam($directory, '.kongono-wiring-');
        if (false === $temporary) {
            $this->fail('temporary_create_failed');
        }
        $temporary = str_replace('\\', '/', $temporary);
        try {
            if (!str_starts_with($temporary, $directory . '/') || is_link($temporary) || !is_file($temporary)) {
                $this->fail('unsafe_temporary_file');
            }
            $handle = @fopen($temporary, 'wb');
            if (false === $handle) {
                $this->fail('temporary_open_failed');
            }
            try {
                $bytes = $validated->canonicalBytes();
                $offset = 0;
                while ($offset < strlen($bytes)) {
                    $written = fwrite($handle, substr($bytes, $offset));
                    if (false === $written || 0 === $written) {
                        $this->fail('temporary_write_failed');
                    }
                    $offset += $written;
                }
                if (!fflush($handle) || function_exists('fsync') && !fsync($handle)) {
                    $this->fail('temporary_sync_failed');
                }
            } finally {
                fclose($handle);
            }
            if (!@link($temporary, $target)) {
                if (file_exists($target)) {
                    $this->acceptExisting($target, $validated->canonicalBytes());
                } else {
                    $this->fail('atomic_publish_failed');
                }
            }
            $this->acceptExisting($target, $validated->canonicalBytes());
        } finally {
            if (file_exists($temporary) && !@unlink($temporary)) {
                $this->fail('temporary_cleanup_failed');
            }
        }

        return $target;
    }

    private function directory(string $directory): string
    {
        $normalized = str_replace('\\', '/', $directory);
        $withoutDrive = 1 === preg_match('/^[A-Za-z]:\//D', $normalized) ? substr($normalized, 2) : $normalized;
        if ('' === $directory || str_contains($directory, "\0") || str_contains($withoutDrive, ':') || str_starts_with($normalized, '//')) {
            $this->fail('invalid_output_directory');
        }
        $resolved = realpath($directory);
        if (false === $resolved || !is_dir($resolved) || is_link($directory)) {
            $this->fail('invalid_output_directory');
        }
        return rtrim(str_replace('\\', '/', $resolved), '/');
    }

    private function acceptExisting(string $target, string $expectedBytes): void
    {
        $resolved = realpath($target);
        if (false === $resolved || str_replace('\\', '/', $resolved) !== $target || is_link($target) || !is_file($target)) {
            $this->fail('unsafe_existing_target');
        }
        $bytes = @file_get_contents($target);
        if (false === $bytes || !hash_equals($expectedBytes, $bytes)) {
            $this->fail('existing_target_conflict');
        }
        (new GeneratedWiringValidator())->validateStructure($bytes);
    }

    private function fail(string $violation): never
    {
        throw new GeneratedWiringPublicationException(CompositionDiagnostic::GeneratedWiringPublicationFailed, $violation);
    }
}
