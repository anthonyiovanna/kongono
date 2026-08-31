<?php

declare(strict_types=1);

namespace Kongono\ExtensionBuild\Artifact;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionBuild\Exception\ExtensionDeclarationPublicationException;
use Kongono\ExtensionBuild\Internal\PathGuard;
use Throwable;

/** Publishes one validated content-addressed declaration artifact without overwrite. */
#[Api(ApiStatus::Experimental)]
final class ExtensionDeclarationArtifactPublisher
{
    public function publish(ExtensionDeclarationArtifact $artifact, string $outputDirectory): string
    {
        try {
            $validated = (new ExtensionDeclarationArtifactValidator())->validateStructure($artifact->canonicalBytes());
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw ExtensionDeclarationPublicationException::failed('invalid_artifact', 'artifact', $failure);
        }
        if (!hash_equals($validated->artifactSha256(), $artifact->artifactSha256())) {
            throw ExtensionDeclarationPublicationException::failed('artifact_identity_mismatch', 'artifact');
        }

        $directory = PathGuard::publicationDirectory($outputDirectory);
        $target = $directory . '/kongono-extension-declarations-v1-' . $validated->artifactSha256() . '.json';
        if (file_exists($target) || is_link($target)) {
            $this->acceptExisting($target, $validated->canonicalBytes());

            return $target;
        }

        $temporary = @tempnam($directory, '.kongono-extension-declarations-');
        if (false === $temporary) {
            throw ExtensionDeclarationPublicationException::failed('temporary_create_failed', 'temporary');
        }
        $temporary = str_replace('\\', '/', $temporary);
        $failure = null;
        try {
            $resolved = realpath($temporary);
            if (
                false === $resolved
                || str_replace('\\', '/', $resolved) !== $temporary
                || !str_starts_with($temporary, $directory . '/')
                || is_link($temporary)
                || !is_file($temporary)
            ) {
                throw ExtensionDeclarationPublicationException::failed('unsafe_temporary_file', 'temporary');
            }
            $handle = @fopen($temporary, 'wb');
            if (false === $handle) {
                throw ExtensionDeclarationPublicationException::failed('temporary_open_failed', 'temporary');
            }
            try {
                $bytes = $validated->canonicalBytes();
                $offset = 0;
                while ($offset < strlen($bytes)) {
                    $written = fwrite($handle, substr($bytes, $offset));
                    if (false === $written || 0 === $written) {
                        throw ExtensionDeclarationPublicationException::failed('temporary_write_failed', 'temporary');
                    }
                    $offset += $written;
                }
                if (!fflush($handle) || (function_exists('fsync') && !fsync($handle))) {
                    throw ExtensionDeclarationPublicationException::failed('temporary_sync_failed', 'temporary');
                }
            } finally {
                fclose($handle);
            }

            if (PathGuard::publicationDirectory($outputDirectory) !== $directory) {
                throw ExtensionDeclarationPublicationException::failed('link_or_reparse', 'path');
            }
            if (!@link($temporary, $target)) {
                if (false !== @lstat($target)) {
                    $this->acceptExisting($target, $validated->canonicalBytes());
                } else {
                    throw ExtensionDeclarationPublicationException::failed('atomic_publish_failed', 'target');
                }
            }
        } catch (Throwable $caught) {
            $failure = $caught;
        } finally {
            if ((file_exists($temporary) || is_link($temporary)) && !@unlink($temporary)) {
                throw ExtensionDeclarationPublicationException::failed('temporary_cleanup_failed', 'temporary', $failure);
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
            throw ExtensionDeclarationPublicationException::failed('unsafe_existing_target', 'target');
        }
        $before = @stat($target);
        if (false === $before || $before['size'] > ExtensionDeclarationArtifactBuilder::MAX_ARTIFACT_BYTES) {
            throw ExtensionDeclarationPublicationException::failed('existing_target_unreadable', 'target');
        }
        $handle = @fopen($target, 'rb');
        if (false === $handle) {
            throw ExtensionDeclarationPublicationException::failed('existing_target_unreadable', 'target');
        }
        $bytes = '';
        try {
            while (!feof($handle)) {
                $remaining = ExtensionDeclarationArtifactBuilder::MAX_ARTIFACT_BYTES + 1 - strlen($bytes);
                $chunk = fread($handle, max(1, min(8192, $remaining)));
                if (false === $chunk) {
                    throw ExtensionDeclarationPublicationException::failed('existing_target_unreadable', 'target');
                }
                $bytes .= $chunk;
                if (strlen($bytes) > ExtensionDeclarationArtifactBuilder::MAX_ARTIFACT_BYTES) {
                    throw ExtensionDeclarationPublicationException::failed('existing_target_conflict', 'target');
                }
            }
            $after = fstat($handle);
        } finally {
            fclose($handle);
        }
        if (false === $after || !$this->sameMetadata($before, $after) || strlen($bytes) !== $before['size']) {
            throw ExtensionDeclarationPublicationException::failed('existing_target_changed', 'target');
        }
        if (!hash_equals($expected, $bytes)) {
            throw ExtensionDeclarationPublicationException::failed('existing_target_conflict', 'target');
        }
        try {
            (new ExtensionDeclarationArtifactValidator())->validateStructure($bytes);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw ExtensionDeclarationPublicationException::failed('existing_target_invalid', 'target', $failure);
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
