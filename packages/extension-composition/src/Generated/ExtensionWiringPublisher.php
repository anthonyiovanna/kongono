<?php

declare(strict_types=1);

namespace Kongono\ExtensionComposition\Generated;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionComposition\Artifact\GeneratedExtensionWiringArtifact;
use Kongono\ExtensionComposition\Exception\ExtensionWiringPublicationException;
use Kongono\ExtensionComposition\Internal\PathGuard;
use Throwable;

/** Publishes validated generated PHP atomically under its exact content identity. */
#[Api(ApiStatus::Experimental)]
final class ExtensionWiringPublisher
{
    public function publish(GeneratedExtensionWiringArtifact $artifact, string $outputDirectory): string
    {
        try {
            $validated = (new ExtensionWiringValidator())->validateStructure($artifact->canonicalBytes());
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            } throw ExtensionWiringPublicationException::failed('invalid_artifact', $failure);
        }
        if (!hash_equals($validated->wiringSha256(), $artifact->wiringSha256())) {
            throw ExtensionWiringPublicationException::failed('artifact_identity_mismatch');
        }
        $directory = PathGuard::publicationDirectory($outputDirectory);
        $target = $directory . '/' . $validated->wiringSha256() . '.php';
        if (file_exists($target) || is_link($target)) {
            $this->acceptExisting($target, $validated->canonicalBytes());
            return $target;
        }
        $temporary = @tempnam($directory, '.kongono-extension-wiring-');
        if (false === $temporary) {
            throw ExtensionWiringPublicationException::failed('temporary_create_failed');
        }
        $temporary = str_replace('\\', '/', $temporary);
        $failure = null;
        try {
            $resolved = realpath($temporary);
            if (false === $resolved || str_replace('\\', '/', $resolved) !== $temporary || !str_starts_with($temporary, $directory . '/') || is_link($temporary) || !is_file($temporary)) {
                throw ExtensionWiringPublicationException::failed('unsafe_temporary_file');
            }
            $handle = @fopen($temporary, 'wb');
            if (false === $handle) {
                throw ExtensionWiringPublicationException::failed('temporary_open_failed');
            }
            try {
                $bytes = $validated->canonicalBytes();
                for ($offset = 0; $offset < strlen($bytes); $offset += $written) {
                    $written = fwrite($handle, substr($bytes, $offset));
                    if (false === $written || 0 === $written) {
                        throw ExtensionWiringPublicationException::failed('temporary_write_failed');
                    }
                }
                if (!fflush($handle) || function_exists('fsync') && !fsync($handle)) {
                    throw ExtensionWiringPublicationException::failed('temporary_sync_failed');
                }
            } finally {
                fclose($handle);
            }
            if (PathGuard::publicationDirectory($outputDirectory) !== $directory) {
                throw ExtensionWiringPublicationException::failed('directory_changed');
            }
            if (!@link($temporary, $target)) {
                if (false !== @lstat($target)) {
                    $this->acceptExisting($target, $validated->canonicalBytes());
                } else {
                    throw ExtensionWiringPublicationException::failed('atomic_publish_failed');
                }
            }
            $this->acceptExisting($target, $validated->canonicalBytes());
        } catch (Throwable $caught) {
            $failure = $caught;
        } finally {
            if ((file_exists($temporary) || is_link($temporary)) && !@unlink($temporary)) {
                throw ExtensionWiringPublicationException::failed('temporary_cleanup_failed', $failure);
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
            throw ExtensionWiringPublicationException::failed('unsafe_existing_target');
        }
        $before = @stat($target);
        if (false === $before || $before['size'] > ExtensionWiringGenerator::MAX_GENERATED_BYTES) {
            throw ExtensionWiringPublicationException::failed('existing_target_unreadable');
        }
        $handle = @fopen($target, 'rb');
        if (false === $handle) {
            throw ExtensionWiringPublicationException::failed('existing_target_unreadable');
        }
        try {
            $bytes = stream_get_contents($handle, ExtensionWiringGenerator::MAX_GENERATED_BYTES + 1);
            $after = fstat($handle);
        } finally {
            fclose($handle);
        }
        if (false === $bytes || false === $after || !$this->sameMetadata($before, $after) || strlen($bytes) !== $before['size']) {
            throw ExtensionWiringPublicationException::failed('existing_target_changed');
        }
        if (!hash_equals($expected, $bytes)) {
            throw ExtensionWiringPublicationException::failed('existing_target_conflict');
        }
        try {
            (new ExtensionWiringValidator())->validateStructure($bytes);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            } throw ExtensionWiringPublicationException::failed('existing_target_invalid', $failure);
        }
    }

    /**
     * @param array<string|int, mixed> $before
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
