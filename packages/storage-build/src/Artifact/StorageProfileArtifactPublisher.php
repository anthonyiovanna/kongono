<?php

declare(strict_types=1);

namespace Kongono\StorageBuild\Artifact;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageBuild\Exception\StorageProfilePublicationException;
use Kongono\StorageBuild\Internal\CanonicalJson;
use Kongono\StorageBuild\Internal\PathGuard;
use Throwable;

/** Atomically publishes one validated content-addressed storage-profile artifact. */
#[Api(ApiStatus::Experimental)]
final class StorageProfileArtifactPublisher
{
    public function publish(StorageProfileArtifact $artifact, string $publicationRoot): StorageProfilePublication
    {
        try {
            $validated = (new StorageProfileArtifactValidator())->validateStructure($artifact->bytes());
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw StorageProfilePublicationException::failed('invalid_artifact', $failure);
        }
        if (!$validated->equals($artifact)) {
            throw StorageProfilePublicationException::failed('invalid_artifact');
        }

        $root = PathGuard::publicationRoot($publicationRoot);
        $digest = $validated->artifactIdentity()->value();
        $relativeArtifactPath = 'objects/sha256/' . substr($digest, 0, 2) . '/' . $digest . '/storage-profile.json';
        $directory = $this->createContainedDirectories($root, dirname($relativeArtifactPath));
        $target = $root . '/' . $relativeArtifactPath;
        if ($this->hasFileSystemEntry($target)) {
            $this->acceptExisting($target, $validated->bytes());

            return $this->publication($publicationRoot, $target, $relativeArtifactPath, $validated, false);
        }

        try {
            $temporary = $directory . '/.storage-profile-' . bin2hex(random_bytes(16)) . '.tmp';
        } catch (Throwable $failure) {
            throw StorageProfilePublicationException::failed('temporary_create_failed', $failure);
        }

        $primary = null;
        $created = false;
        try {
            $this->writeExclusive($temporary, $validated->bytes());
            if (PathGuard::publicationRoot($publicationRoot) !== $root || !$this->isExactDirectory($directory)) {
                throw StorageProfilePublicationException::failed('path_changed');
            }
            if (@rename($temporary, $target)) {
                $temporary = '';
                $created = true;
            } elseif ($this->hasFileSystemEntry($target)) {
                $this->acceptExisting($target, $validated->bytes());
            } else {
                throw StorageProfilePublicationException::failed('atomic_publish_failed');
            }
        } catch (Throwable $failure) {
            $primary = $failure;
        }

        if ('' !== $temporary && !$this->cleanupTemporary($temporary)) {
            throw StorageProfilePublicationException::failed('temporary_cleanup_failed', $primary, 'failed');
        }
        if (null !== $primary) {
            if ($primary instanceof \Error || $primary instanceof StorageProfilePublicationException) {
                throw $primary;
            }
            throw StorageProfilePublicationException::failed('atomic_publish_failed', $primary, 'complete');
        }

        return $this->publication($publicationRoot, $target, $relativeArtifactPath, $validated, $created);
    }

    private function publication(
        string $publicationRoot,
        string $absoluteArtifactPath,
        string $relativeArtifactPath,
        StorageProfileArtifact $artifact,
        bool $created,
    ): StorageProfilePublication {
        return new StorageProfilePublication(
            $publicationRoot,
            $absoluteArtifactPath,
            $relativeArtifactPath,
            $artifact->artifactIdentity(),
            $artifact->byteCount(),
            $created,
        );
    }

    private function createContainedDirectories(string $root, string $relative): string
    {
        $current = $root;
        foreach (explode('/', $relative) as $segment) {
            $current .= '/' . $segment;
            if (false === @lstat($current) && !@mkdir($current, 0700)) {
                throw StorageProfilePublicationException::failed('temporary_create_failed');
            }
            if (!$this->isExactDirectory($current)) {
                throw StorageProfilePublicationException::failed('unsafe_publication_component');
            }
        }

        return $current;
    }

    private function isExactDirectory(string $path): bool
    {
        $resolved = realpath($path);
        $stat = @lstat($path);

        return false !== $resolved
            && PathGuard::normalize($resolved) === $path
            && false !== $stat
            && 0040000 === ($stat['mode'] & 0170000)
            && !is_link($path);
    }

    private function hasFileSystemEntry(string $path): bool
    {
        clearstatcache(true, $path);

        return false !== @lstat($path);
    }

    private function writeExclusive(string $path, string $bytes): void
    {
        $handle = @fopen($path, 'xb');
        if (false === $handle) {
            throw StorageProfilePublicationException::failed('temporary_create_failed');
        }
        $failure = null;
        try {
            $offset = 0;
            while ($offset < strlen($bytes)) {
                $written = @fwrite($handle, substr($bytes, $offset, 8192));
                if (false === $written || 0 === $written) {
                    throw StorageProfilePublicationException::failed('temporary_write_failed');
                }
                $offset += $written;
            }
            if (!@fflush($handle) || (function_exists('fsync') && !@fsync($handle))) {
                throw StorageProfilePublicationException::failed('temporary_sync_failed');
            }
            $stat = @fstat($handle);
            if (false === $stat || $stat['size'] !== strlen($bytes)) {
                throw StorageProfilePublicationException::failed('temporary_write_failed');
            }
        } catch (Throwable $caught) {
            $failure = $caught;
        } finally {
            if (!@fclose($handle) && null === $failure) {
                $failure = StorageProfilePublicationException::failed('temporary_close_failed');
            }
        }
        if (null !== $failure) {
            throw $failure;
        }
    }

    private function acceptExisting(string $path, string $expectedBytes): void
    {
        $resolved = realpath($path);
        $stat = @lstat($path);
        if (false === $resolved
            || PathGuard::normalize($resolved) !== $path
            || false === $stat
            || 0100000 !== ($stat['mode'] & 0170000)
            || is_link($path)) {
            throw StorageProfilePublicationException::failed('unsafe_existing_target');
        }
        if (!hash_equals($expectedBytes, $this->readStable($path, CanonicalJson::MAX_BYTES))) {
            throw StorageProfilePublicationException::failed('existing_target_conflict');
        }
    }

    private function readStable(string $path, int $maximum): string
    {
        $before = @stat($path);
        if (false === $before || $before['size'] > $maximum) {
            throw StorageProfilePublicationException::failed('existing_target_unreadable');
        }
        $handle = @fopen($path, 'rb');
        if (false === $handle) {
            throw StorageProfilePublicationException::failed('existing_target_unreadable');
        }
        $bytes = '';
        $after = false;
        $failure = null;
        try {
            while (!feof($handle)) {
                $chunk = @fread($handle, min(8192, max(1, $maximum + 1 - strlen($bytes))));
                if (false === $chunk) {
                    throw StorageProfilePublicationException::failed('existing_target_unreadable');
                }
                $bytes .= $chunk;
                if (strlen($bytes) > $maximum) {
                    throw StorageProfilePublicationException::failed('existing_target_conflict');
                }
            }
            $after = @fstat($handle);
        } catch (Throwable $caught) {
            $failure = $caught;
        } finally {
            if (!@fclose($handle) && null === $failure) {
                $failure = StorageProfilePublicationException::failed('existing_target_unreadable');
            }
        }
        if (null !== $failure) {
            throw $failure;
        }
        if (false === $after || strlen($bytes) !== $before['size']) {
            throw StorageProfilePublicationException::failed('existing_target_changed');
        }
        foreach (['dev', 'ino', 'mode', 'size', 'mtime', 'ctime'] as $key) {
            if ($before[$key] !== $after[$key]) {
                throw StorageProfilePublicationException::failed('existing_target_changed');
            }
        }

        return $bytes;
    }

    private function cleanupTemporary(string $path): bool
    {
        $stat = @lstat($path);
        if (false === $stat) {
            return true;
        }
        $resolved = realpath($path);

        return false !== $resolved
            && PathGuard::normalize($resolved) === $path
            && 0100000 === ($stat['mode'] & 0170000)
            && !is_link($path)
            && @unlink($path);
    }
}
