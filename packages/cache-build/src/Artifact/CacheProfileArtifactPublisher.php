<?php

declare(strict_types=1);

namespace Kongono\CacheBuild\Artifact;

use FilesystemIterator;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheBuild\Exception\CacheProfilePublicationException;
use Kongono\CacheBuild\Internal\CanonicalJson;
use Kongono\CacheBuild\Internal\PathGuard;
use Throwable;

/** Atomically publishes one validated content-addressed cache-profile artifact. */
#[Api(ApiStatus::Experimental)]
final class CacheProfileArtifactPublisher
{
    public function publish(CacheProfileArtifact $artifact, string $publicationRoot): CacheProfilePublication
    {
        try {
            $validated = (new CacheProfileArtifactValidator())->validateStructure($artifact->bytes());
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw CacheProfilePublicationException::failed('invalid_artifact', $failure);
        }
        if (!$validated->equals($artifact)) {
            throw CacheProfilePublicationException::failed('invalid_artifact');
        }
        $root = PathGuard::publicationRoot($publicationRoot);
        $digest = $validated->artifactIdentity()->value();
        $relativeDirectory = 'objects/sha256/' . substr($digest, 0, 2) . '/' . $digest;
        $relativeArtifactPath = $relativeDirectory . '/cache-profile.json';
        $parent = $this->createContainedDirectories($root, 'objects/sha256/' . substr($digest, 0, 2));
        $target = $root . '/' . $relativeDirectory;
        if (file_exists($target) || is_link($target)) {
            $this->acceptExisting($target, $validated->bytes());

            return new CacheProfilePublication($validated->artifactIdentity(), $relativeDirectory, $relativeArtifactPath, true);
        }
        try {
            $temporary = $parent . '/.' . $digest . '-' . bin2hex(random_bytes(16)) . '.tmp';
        } catch (Throwable $failure) {
            throw CacheProfilePublicationException::failed('temporary_create_failed', $failure);
        }
        if (!@mkdir($temporary, 0700)) {
            throw CacheProfilePublicationException::failed('temporary_create_failed');
        }
        $primary = null;
        try {
            $this->write($temporary . '/cache-profile.json', $validated->bytes());
            if (PathGuard::publicationRoot($publicationRoot) !== $root) {
                throw CacheProfilePublicationException::failed('path_changed');
            }
            $this->acceptExisting($temporary, $validated->bytes());
            if (!@rename($temporary, $target)) {
                if (false !== @lstat($target)) {
                    $this->acceptExisting($target, $validated->bytes());
                } else {
                    throw CacheProfilePublicationException::failed('atomic_publish_failed');
                }
            } else {
                $temporary = '';
            }
        } catch (Throwable $failure) {
            $primary = $failure;
        }
        if ('' !== $temporary && !$this->cleanup($temporary)) {
            throw CacheProfilePublicationException::failed('temporary_cleanup_failed', $primary, 'failed');
        }
        if (null !== $primary) {
            if ($primary instanceof \Error || $primary instanceof CacheProfilePublicationException) {
                throw $primary;
            }
            throw CacheProfilePublicationException::failed('atomic_publish_failed', $primary, 'complete');
        }

        return new CacheProfilePublication($validated->artifactIdentity(), $relativeDirectory, $relativeArtifactPath, false);
    }

    private function createContainedDirectories(string $root, string $relative): string
    {
        $current = $root;
        foreach (explode('/', $relative) as $segment) {
            $current .= '/' . $segment;
            if (!file_exists($current) && !@mkdir($current, 0700)) {
                throw CacheProfilePublicationException::failed('temporary_create_failed');
            }
            $resolved = realpath($current);
            if (false === $resolved || PathGuard::normalize($resolved) !== $current || is_link($current) || !is_dir($current)) {
                throw CacheProfilePublicationException::failed('unsafe_publication_component');
            }
        }

        return $current;
    }

    private function write(string $path, string $bytes): void
    {
        $handle = @fopen($path, 'xb');
        if (false === $handle) {
            throw CacheProfilePublicationException::failed('temporary_write_failed');
        }
        $failure = null;
        try {
            $offset = 0;
            while ($offset < strlen($bytes)) {
                $written = @fwrite($handle, substr($bytes, $offset, 8192));
                if (false === $written || 0 === $written) {
                    throw CacheProfilePublicationException::failed('temporary_write_failed');
                }
                $offset += $written;
            }
            if (!@fflush($handle) || (function_exists('fsync') && !@fsync($handle))) {
                throw CacheProfilePublicationException::failed('temporary_sync_failed');
            }
            $stat = @fstat($handle);
            if (false === $stat || $stat['size'] !== strlen($bytes)) {
                throw CacheProfilePublicationException::failed('temporary_write_failed');
            }
        } catch (Throwable $caught) {
            $failure = $caught;
        } finally {
            if (!@fclose($handle) && null === $failure) {
                $failure = CacheProfilePublicationException::failed('temporary_sync_failed');
            }
        }
        if (null !== $failure) {
            throw $failure;
        }
    }

    private function acceptExisting(string $target, string $expectedBytes): void
    {
        $resolved = realpath($target);
        if (false === $resolved || PathGuard::normalize($resolved) !== $target || is_link($target) || !is_dir($target)) {
            throw CacheProfilePublicationException::failed('unsafe_existing_target');
        }
        $entries = @scandir($target);
        if (false === $entries || ['.', '..', 'cache-profile.json'] !== $entries) {
            throw CacheProfilePublicationException::failed('existing_target_conflict');
        }
        $path = $target . '/cache-profile.json';
        if (!hash_equals($expectedBytes, $this->readStable($path, CanonicalJson::MAX_BYTES))) {
            throw CacheProfilePublicationException::failed('existing_target_conflict');
        }
    }

    private function readStable(string $path, int $maximum): string
    {
        $resolved = realpath($path);
        if (false === $resolved || PathGuard::normalize($resolved) !== $path || is_link($path) || !is_file($path)) {
            throw CacheProfilePublicationException::failed('unsafe_existing_target');
        }
        $before = @stat($path);
        if (false === $before || $before['size'] > $maximum) {
            throw CacheProfilePublicationException::failed('existing_target_unreadable');
        }
        $handle = @fopen($path, 'rb');
        if (false === $handle) {
            throw CacheProfilePublicationException::failed('existing_target_unreadable');
        }
        $bytes = '';
        try {
            while (!feof($handle)) {
                $chunk = @fread($handle, min(8192, max(1, $maximum + 1 - strlen($bytes))));
                if (false === $chunk) {
                    throw CacheProfilePublicationException::failed('existing_target_unreadable');
                }
                $bytes .= $chunk;
                if (strlen($bytes) > $maximum) {
                    throw CacheProfilePublicationException::failed('existing_target_conflict');
                }
            }
            $after = @fstat($handle);
        } finally {
            @fclose($handle);
        }
        if (false === $after || strlen($bytes) !== $before['size']) {
            throw CacheProfilePublicationException::failed('existing_target_changed');
        }
        foreach (['dev', 'ino', 'mode', 'size', 'mtime', 'ctime'] as $key) {
            if ($before[$key] !== $after[$key]) {
                throw CacheProfilePublicationException::failed('existing_target_changed');
            }
        }

        return $bytes;
    }

    private function cleanup(string $directory): bool
    {
        if (!is_dir($directory) || is_link($directory)) {
            return !file_exists($directory);
        }
        $iterator = new FilesystemIterator($directory, FilesystemIterator::SKIP_DOTS);
        foreach ($iterator as $entry) {
            if (!$entry instanceof \SplFileInfo || $entry->isLink() || !$entry->isFile() || !@unlink($entry->getPathname())) {
                return false;
            }
        }

        return @rmdir($directory);
    }
}
