<?php

declare(strict_types=1);

namespace Kongono\AssetBuild\Artifact;

use FilesystemIterator;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetBuild\Build\AssetBuildResult;
use Kongono\AssetBuild\Exception\AssetPublicationException;
use Kongono\AssetBuild\Internal\CanonicalJson;
use Kongono\AssetBuild\Internal\PathGuard;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

/** Atomically publishes one validated content-addressed native asset directory. */
#[Api(ApiStatus::Experimental)]
final class AssetBuildArtifactPublisher
{
    public function publish(AssetBuildResult $result, string $outputDirectory): AssetPublication
    {
        try {
            $validated = (new AssetBuildArtifactValidator())->validateStructure($result->artifact()->bytes(), $result->outputBytesByPath());
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw AssetPublicationException::failed('invalid_result', $failure);
        }
        if (!$validated->equals($result->artifact())) {
            throw AssetPublicationException::failed('invalid_result');
        }
        $root = PathGuard::publicationDirectory($outputDirectory);
        $name = 'kongono-assets-v1-' . $validated->buildIdentity()->value();
        $target = $root . '/' . $name;
        $expected = ['artifact.json' => $validated->bytes(), ...$result->outputBytesByPath()];
        ksort($expected, SORT_STRING);
        if (file_exists($target) || is_link($target)) {
            $this->acceptExisting($target, $expected);

            return new AssetPublication($validated->buildIdentity(), $name, array_keys($result->outputBytesByPath()));
        }
        try {
            $temporary = $root . '/.kongono-assets-v1-' . $validated->buildIdentity()->value() . '-' . bin2hex(random_bytes(16)) . '.tmp';
        } catch (Throwable $failure) {
            throw AssetPublicationException::failed('temporary_create_failed', $failure);
        }
        if (!@mkdir($temporary, 0700)) {
            throw AssetPublicationException::failed('temporary_create_failed');
        }
        $primary = null;
        try {
            foreach ($expected as $relative => $bytes) {
                $this->createParents($temporary, $relative);
                $this->write($temporary . '/' . $relative, $bytes);
            }
            if (PathGuard::publicationDirectory($outputDirectory) !== $root) {
                throw AssetPublicationException::failed('path_changed');
            }
            $this->acceptExisting($temporary, $expected);
            if (!@rename($temporary, $target)) {
                if (false !== @lstat($target)) {
                    $this->acceptExisting($target, $expected);
                } else {
                    throw AssetPublicationException::failed('atomic_publish_failed');
                }
            } else {
                $temporary = '';
            }
        } catch (Throwable $failure) {
            $primary = $failure;
        }
        if ('' !== $temporary && !$this->cleanup($temporary)) {
            throw AssetPublicationException::failed('temporary_cleanup_failed', $primary, 'failed');
        }
        if (null !== $primary) {
            if ($primary instanceof \Error || $primary instanceof AssetPublicationException) {
                throw $primary;
            }
            throw AssetPublicationException::failed('atomic_publish_failed', $primary, 'complete');
        }

        return new AssetPublication($validated->buildIdentity(), $name, array_keys($result->outputBytesByPath()));
    }

    private function createParents(string $temporary, string $relative): void
    {
        $parent = dirname($relative);
        if ('.' === $parent) {
            return;
        }
        $current = $temporary;
        foreach (explode('/', $parent) as $segment) {
            $current .= '/' . $segment;
            if (is_dir($current)) {
                continue;
            }
            if (!@mkdir($current, 0700)) {
                throw AssetPublicationException::failed('temporary_create_failed');
            }
        }
    }

    private function write(string $path, string $bytes): void
    {
        $handle = @fopen($path, 'xb');
        if (false === $handle) {
            throw AssetPublicationException::failed('temporary_write_failed');
        }
        $failure = null;
        try {
            $offset = 0;
            while ($offset < strlen($bytes)) {
                $written = @fwrite($handle, substr($bytes, $offset, 8192));
                if (false === $written || 0 === $written) {
                    throw AssetPublicationException::failed('temporary_write_failed');
                }
                $offset += $written;
            }
            if (!@fflush($handle) || (function_exists('fsync') && !@fsync($handle))) {
                throw AssetPublicationException::failed('temporary_sync_failed');
            }
            $stat = @fstat($handle);
            if (false === $stat || $stat['size'] !== strlen($bytes)) {
                throw AssetPublicationException::failed('temporary_write_failed');
            }
        } catch (Throwable $caught) {
            $failure = $caught;
        } finally {
            if (!@fclose($handle) && null === $failure) {
                $failure = AssetPublicationException::failed('temporary_sync_failed');
            }
        }
        if (null !== $failure) {
            throw $failure;
        }
    }

    /** @param array<string, string> $expected */
    private function acceptExisting(string $target, array $expected): void
    {
        $resolved = realpath($target);
        if (false === $resolved || PathGuard::normalize($resolved) !== $target || is_link($target) || !is_dir($target)) {
            throw AssetPublicationException::failed('unsafe_existing_target');
        }
        $actualFiles = [];
        $actualDirectories = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($target, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $entry) {
            if (!$entry instanceof \SplFileInfo) {
                throw AssetPublicationException::failed('unsafe_existing_target');
            }
            $path = PathGuard::normalize($entry->getPathname());
            if (is_link($path)) {
                throw AssetPublicationException::failed('unsafe_existing_target');
            }
            $relative = substr($path, strlen($target) + 1);
            $real = realpath($path);
            if (false === $real || PathGuard::normalize($real) !== $path) {
                throw AssetPublicationException::failed('unsafe_existing_target');
            }
            if ($entry->isDir()) {
                $actualDirectories[] = $relative;
            } elseif ($entry->isFile()) {
                $actualFiles[] = $relative;
            } else {
                throw AssetPublicationException::failed('unsafe_existing_target');
            }
        }
        sort($actualFiles, SORT_STRING);
        $expectedFiles = array_keys($expected);
        sort($expectedFiles, SORT_STRING);
        $expectedDirectories = [];
        foreach ($expectedFiles as $relative) {
            $parent = dirname($relative);
            while ('.' !== $parent && !isset($expectedDirectories[$parent])) {
                $expectedDirectories[$parent] = true;
                $parent = dirname($parent);
            }
        }
        $expectedDirectoryList = array_keys($expectedDirectories);
        sort($expectedDirectoryList, SORT_STRING);
        sort($actualDirectories, SORT_STRING);
        if ($actualFiles !== $expectedFiles || $actualDirectories !== $expectedDirectoryList) {
            throw AssetPublicationException::failed('existing_target_conflict');
        }
        foreach ($expected as $relative => $bytes) {
            if (!hash_equals($bytes, $this->readStable($target . '/' . $relative, max(CanonicalJson::MAX_BYTES, strlen($bytes))))) {
                throw AssetPublicationException::failed('existing_target_conflict');
            }
        }
    }

    private function readStable(string $path, int $maximum): string
    {
        $resolved = realpath($path);
        if (false === $resolved || PathGuard::normalize($resolved) !== $path || is_link($path) || !is_file($path)) {
            throw AssetPublicationException::failed('unsafe_existing_target');
        }
        $before = @stat($path);
        if (false === $before || $before['size'] > $maximum) {
            throw AssetPublicationException::failed('existing_target_unreadable');
        }
        $handle = @fopen($path, 'rb');
        if (false === $handle) {
            throw AssetPublicationException::failed('existing_target_unreadable');
        }
        $bytes = '';
        try {
            while (!feof($handle)) {
                $remaining = max(1, $maximum + 1 - strlen($bytes));
                $chunk = @fread($handle, min(8192, $remaining));
                if (false === $chunk) {
                    throw AssetPublicationException::failed('existing_target_unreadable');
                }
                $bytes .= $chunk;
                if (strlen($bytes) > $maximum) {
                    throw AssetPublicationException::failed('existing_target_conflict');
                }
            }
            $after = @fstat($handle);
        } finally {
            @fclose($handle);
        }
        if (false === $after || strlen($bytes) !== $before['size']) {
            throw AssetPublicationException::failed('existing_target_changed');
        }
        foreach (['dev', 'ino', 'mode', 'size', 'mtime', 'ctime'] as $key) {
            if ($before[$key] !== $after[$key]) {
                throw AssetPublicationException::failed('existing_target_changed');
            }
        }

        return $bytes;
    }

    private function cleanup(string $directory): bool
    {
        if (!is_dir($directory) || is_link($directory)) {
            return !file_exists($directory);
        }
        $entries = @scandir($directory);
        if (false === $entries) {
            return false;
        }
        foreach ($entries as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }
            $path = $directory . '/' . $entry;
            if (is_link($path)) {
                if (!@unlink($path)) {
                    return false;
                }
            } elseif (is_dir($path)) {
                if (!$this->cleanup($path)) {
                    return false;
                }
            } elseif (!@unlink($path)) {
                return false;
            }
        }

        return @rmdir($directory);
    }
}
