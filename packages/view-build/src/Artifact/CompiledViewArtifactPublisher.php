<?php

declare(strict_types=1);

namespace Kongono\ViewBuild\Artifact;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewBuild\Exception\ViewArtifactPublicationException;
use Kongono\ViewBuild\Internal\CanonicalJson;
use Kongono\ViewBuild\Internal\PathGuard;
use Throwable;

/** Atomically publishes one validated two-file content-addressed artifact directory. */
#[Api(ApiStatus::Experimental)]
final class CompiledViewArtifactPublisher
{
    public function publish(CompiledViewArtifact $artifact, string $outputDirectory): string
    {
        try {
            $validated = (new CompiledViewArtifactValidator())->validateStructure($artifact->metadataBytes(), $artifact->generatedPhpBytes());
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw ViewArtifactPublicationException::failed('invalid_artifact', $failure);
        }
        if (!$validated->equals($artifact)) {
            throw ViewArtifactPublicationException::failed('artifact_identity_mismatch');
        }
        $directory = PathGuard::publicationDirectory($outputDirectory);
        $target = $directory . '/kongono-view-v1-' . $validated->compiledViewIdentity()->value();
        if (file_exists($target) || is_link($target)) {
            $this->acceptExisting($target, $validated);

            return $target;
        }

        try {
            $temporary = $directory . '/.kongono-view-v1-' . $validated->compiledViewIdentity()->value() . '-' . bin2hex(random_bytes(16)) . '.tmp';
        } catch (Throwable $failure) {
            throw ViewArtifactPublicationException::failed('temporary_create_failed', $failure);
        }
        if (!@mkdir($temporary, 0700)) {
            throw ViewArtifactPublicationException::failed('temporary_create_failed');
        }
        $failure = null;
        try {
            $this->assertContainedDirectory($temporary, $directory);
            $this->write($temporary . '/artifact.json', $validated->metadataBytes());
            $this->write($temporary . '/compiled.php', $validated->generatedPhpBytes());
            if (PathGuard::publicationDirectory($outputDirectory) !== $directory) {
                throw ViewArtifactPublicationException::failed('path_changed');
            }
            if (!@rename($temporary, $target)) {
                if (false !== @lstat($target)) {
                    $this->acceptExisting($target, $validated);
                } else {
                    throw ViewArtifactPublicationException::failed('atomic_publish_failed');
                }
            } else {
                $temporary = '';
            }
        } catch (Throwable $caught) {
            $failure = $caught;
        }
        if ('' !== $temporary && !$this->cleanup($temporary)) {
            throw ViewArtifactPublicationException::failed('temporary_cleanup_failed', $failure);
        }
        if (null !== $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            if ($failure instanceof ViewArtifactPublicationException) {
                throw $failure;
            }
            throw ViewArtifactPublicationException::failed('atomic_publish_failed', $failure);
        }

        return $target;
    }

    private function write(string $path, string $bytes): void
    {
        $handle = @fopen($path, 'xb');
        if (false === $handle) {
            throw ViewArtifactPublicationException::failed('temporary_write_failed');
        }
        $failure = null;
        try {
            $offset = 0;
            while ($offset < strlen($bytes)) {
                $written = @fwrite($handle, substr($bytes, $offset));
                if (false === $written || 0 === $written) {
                    throw ViewArtifactPublicationException::failed('temporary_write_failed');
                }
                $offset += $written;
            }
            if (!@fflush($handle) || function_exists('fsync') && !@fsync($handle)) {
                throw ViewArtifactPublicationException::failed('temporary_sync_failed');
            }
            $stat = @fstat($handle);
            if (false === $stat || $stat['size'] !== strlen($bytes)) {
                throw ViewArtifactPublicationException::failed('temporary_write_failed');
            }
        } catch (Throwable $caught) {
            $failure = $caught;
        } finally {
            if (!@fclose($handle) && null === $failure) {
                $failure = ViewArtifactPublicationException::failed('temporary_sync_failed');
            }
        }
        if (null !== $failure) {
            throw $failure;
        }
    }

    private function acceptExisting(string $target, CompiledViewArtifact $artifact): void
    {
        $resolved = realpath($target);
        if (false === $resolved || self::normalize($resolved) !== $target || is_link($target) || !is_dir($target)) {
            throw ViewArtifactPublicationException::failed('unsafe_existing_target');
        }
        $entries = @scandir($target);
        if (false === $entries || ['.', '..', 'artifact.json', 'compiled.php'] !== $entries) {
            throw ViewArtifactPublicationException::failed('existing_target_conflict');
        }
        $metadata = $this->readStable($target . '/artifact.json', CanonicalJson::MAX_BYTES);
        $generated = $this->readStable($target . '/compiled.php', CanonicalJson::MAX_BYTES);
        if (!hash_equals($artifact->metadataBytes(), $metadata) || !hash_equals($artifact->generatedPhpBytes(), $generated)) {
            throw ViewArtifactPublicationException::failed('existing_target_conflict');
        }
        try {
            (new CompiledViewArtifactValidator())->validateStructure($metadata, $generated);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw ViewArtifactPublicationException::failed('existing_target_conflict', $failure);
        }
    }

    private function readStable(string $path, int $maximum): string
    {
        $resolved = realpath($path);
        if (false === $resolved || self::normalize($resolved) !== $path || is_link($path) || !is_file($path)) {
            throw ViewArtifactPublicationException::failed('unsafe_existing_target');
        }
        $before = @stat($path);
        if (false === $before || $before['size'] > $maximum) {
            throw ViewArtifactPublicationException::failed('existing_target_unreadable');
        }
        $handle = @fopen($path, 'rb');
        if (false === $handle) {
            throw ViewArtifactPublicationException::failed('existing_target_unreadable');
        }
        $bytes = '';
        try {
            while (!feof($handle)) {
                $remaining = $maximum + 1 - strlen($bytes);
                $chunk = @fread($handle, max(1, min(8192, $remaining)));
                if (false === $chunk) {
                    throw ViewArtifactPublicationException::failed('existing_target_unreadable');
                }
                $bytes .= $chunk;
                if (strlen($bytes) > $maximum) {
                    throw ViewArtifactPublicationException::failed('existing_target_conflict');
                }
            }
            $after = @fstat($handle);
        } finally {
            @fclose($handle);
        }
        if (false === $after) {
            throw ViewArtifactPublicationException::failed('existing_target_unreadable');
        }
        foreach (['dev', 'ino', 'mode', 'size', 'mtime', 'ctime'] as $key) {
            if ($before[$key] !== $after[$key]) {
                throw ViewArtifactPublicationException::failed('existing_target_changed');
            }
        }
        if (strlen($bytes) !== $before['size']) {
            throw ViewArtifactPublicationException::failed('existing_target_changed');
        }

        return $bytes;
    }

    private function assertContainedDirectory(string $temporary, string $parent): void
    {
        $resolved = realpath($temporary);
        if (false === $resolved || self::normalize($resolved) !== $temporary || !str_starts_with($temporary, $parent . '/') || is_link($temporary) || !is_dir($temporary)) {
            throw ViewArtifactPublicationException::failed('unsafe_temporary_directory');
        }
    }

    private function cleanup(string $temporary): bool
    {
        foreach ([$temporary . '/artifact.json', $temporary . '/compiled.php'] as $path) {
            if ((file_exists($path) || is_link($path)) && !@unlink($path)) {
                return false;
            }
        }

        return !file_exists($temporary) || @rmdir($temporary);
    }

    private static function normalize(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);
        if (1 === preg_match('/\A[A-Z]:\//D', $normalized)) {
            $normalized = strtolower($normalized[0]) . substr($normalized, 1);
        }

        return $normalized;
    }
}
