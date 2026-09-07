<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationApplication\Generated;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationApplication\Exception\ModelInvocationWiringPublicationException;
use Kongono\ModelInvocationApplication\Internal\PathGuard;
use Throwable;

/** Publishes exact generated PHP atomically, idempotently, initial-only, and without clobber. */
#[Api(ApiStatus::Experimental)]
final class ModelInvocationWiringPublisher
{
    public function publish(string $applicationRoot, string $outputDirectory, GeneratedModelInvocationWiring $wiring): string
    {
        try {
            $validated = (new ModelInvocationWiringValidator())->validateStructure($wiring->canonicalBytes());
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw ModelInvocationWiringPublicationException::failed('publish', $failure);
        }
        if (!hash_equals($validated->generatedWiringSha256(), $wiring->generatedWiringSha256())) {
            throw ModelInvocationWiringPublicationException::failed('publish');
        }
        [, $directory] = PathGuard::publicationLocations($applicationRoot, $outputDirectory);
        $target = $directory . '/kongono-model-invocation-wiring-v1-' . $validated->generatedWiringSha256() . '.php';
        if (file_exists($target) || is_link($target)) {
            $this->acceptExisting($target, $validated->canonicalBytes());

            return $target;
        }

        $temporary = @tempnam($directory, '.kongono-model-invocation-wiring-');
        if (false === $temporary) {
            throw ModelInvocationWiringPublicationException::failed('temporary_create');
        }
        $temporary = str_replace('\\', '/', $temporary);
        $failure = null;
        try {
            $resolved = realpath($temporary);
            if (false === $resolved || str_replace('\\', '/', $resolved) !== $temporary || !str_starts_with($temporary, $directory . '/') || is_link($temporary) || !is_file($temporary)) {
                throw ModelInvocationWiringPublicationException::failed('temporary_create');
            }
            $handle = @fopen($temporary, 'xb');
            if (false === $handle) {
                $handle = @fopen($temporary, 'wb');
            }
            if (false === $handle) {
                throw ModelInvocationWiringPublicationException::failed('temporary_create');
            }
            try {
                $bytes = $validated->canonicalBytes();
                $offset = 0;
                while ($offset < strlen($bytes)) {
                    $written = @fwrite($handle, substr($bytes, $offset));
                    if (false === $written || 0 === $written) {
                        throw ModelInvocationWiringPublicationException::failed('temporary_write');
                    }
                    $offset += $written;
                }
                if (!@fflush($handle) || (function_exists('fsync') && !@fsync($handle))) {
                    throw ModelInvocationWiringPublicationException::failed('temporary_sync');
                }
            } finally {
                @fclose($handle);
            }
            [, $reverifiedDirectory] = PathGuard::publicationLocations($applicationRoot, $outputDirectory);
            if ($reverifiedDirectory !== $directory) {
                throw ModelInvocationWiringPublicationException::failed('path');
            }
            if (!@link($temporary, $target)) {
                if (false !== @lstat($target)) {
                    $this->acceptExisting($target, $validated->canonicalBytes());
                } else {
                    throw ModelInvocationWiringPublicationException::failed('publish');
                }
            }
            $this->acceptExisting($target, $validated->canonicalBytes());
        } catch (Throwable $caught) {
            $failure = $caught;
        } finally {
            if ((file_exists($temporary) || is_link($temporary)) && !@unlink($temporary)) {
                throw ModelInvocationWiringPublicationException::failed('temporary_cleanup', $failure);
            }
        }
        if (null !== $failure) {
            throw $failure;
        }

        return $target;
    }

    private function acceptExisting(string $target, string $expected): void
    {
        $resolved = realpath($target);
        if (false === $resolved || str_replace('\\', '/', $resolved) !== $target || is_link($target) || !is_file($target)) {
            throw ModelInvocationWiringPublicationException::failed('existing_target');
        }
        $before = @stat($target);
        if (false === $before || $before['size'] > ModelInvocationWiringGenerator::MAX_GENERATED_BYTES) {
            throw ModelInvocationWiringPublicationException::failed('existing_target');
        }
        $handle = @fopen($target, 'rb');
        if (false === $handle) {
            throw ModelInvocationWiringPublicationException::failed('existing_target');
        }
        try {
            $bytes = stream_get_contents($handle, ModelInvocationWiringGenerator::MAX_GENERATED_BYTES + 1);
            $after = fstat($handle);
        } finally {
            fclose($handle);
        }
        if (false === $bytes || false === $after || !$this->sameMetadata($before, $after) || strlen($bytes) !== $before['size'] || !hash_equals($expected, $bytes)) {
            throw ModelInvocationWiringPublicationException::failed('existing_target');
        }
        try {
            (new ModelInvocationWiringValidator())->validateStructure($bytes);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw ModelInvocationWiringPublicationException::failed('existing_target', $failure);
        }
    }

    /**
     * @param array<string|int, int> $before
     * @param array<string|int, int> $after
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
