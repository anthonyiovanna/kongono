<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationBuild\Artifact;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationBuild\Exception\InvocationPlanPublicationException;
use Kongono\ModelInvocationBuild\Internal\PathGuard;
use Throwable;

/** Publishes one validated content-addressed invocation-plan artifact without overwrite. */
#[Api(ApiStatus::Experimental)]
final class InvocationPlanArtifactPublisher
{
    private const int MAX_ARTIFACT_BYTES = 65_536;

    public function publish(InvocationPlanArtifact $artifact, string $outputDirectory): string
    {
        try {
            $validated = (new InvocationPlanArtifactValidator())->validateStructure($artifact->canonicalBytes());
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw InvocationPlanPublicationException::failed('invalid_artifact', $failure);
        }
        if (!hash_equals($validated->artifactSha256(), $artifact->artifactSha256())) {
            throw InvocationPlanPublicationException::failed('artifact_identity_mismatch');
        }

        $directory = PathGuard::publicationDirectory($outputDirectory);
        $target = $directory . '/kongono-model-invocation-plan-v1-' . $validated->artifactSha256() . '.json';
        if (file_exists($target) || is_link($target)) {
            $this->acceptExisting($target, $validated->canonicalBytes());

            return $target;
        }

        $temporary = $this->temporaryPath($directory, $validated->artifactSha256());
        $handle = @fopen($temporary, 'xb');
        if (false === $handle) {
            throw InvocationPlanPublicationException::failed('temporary_open_failed');
        }
        $failure = null;
        try {
            if (is_link($temporary) || !is_file($temporary) || !str_starts_with($temporary, $directory . '/')) {
                throw InvocationPlanPublicationException::failed('unsafe_temporary_file');
            }
            $bytes = $validated->canonicalBytes();
            $offset = 0;
            while ($offset < strlen($bytes)) {
                $written = @fwrite($handle, substr($bytes, $offset));
                if (false === $written || 0 === $written) {
                    throw InvocationPlanPublicationException::failed('temporary_write_failed');
                }
                $offset += $written;
            }
            if (!@fflush($handle) || (function_exists('fsync') && !@fsync($handle))) {
                throw InvocationPlanPublicationException::failed('temporary_sync_failed');
            }
            $stat = @fstat($handle);
            if (false === $stat || $stat['size'] !== strlen($bytes)) {
                throw InvocationPlanPublicationException::failed('temporary_write_failed');
            }
        } catch (Throwable $caught) {
            $failure = $caught;
        } finally {
            if (!@fclose($handle) && null === $failure) {
                $failure = InvocationPlanPublicationException::failed('temporary_sync_failed');
            }
        }

        if (null === $failure) {
            try {
                if (PathGuard::publicationDirectory($outputDirectory) !== $directory) {
                    throw InvocationPlanPublicationException::failed('link_or_reparse');
                }
                if (!@link($temporary, $target)) {
                    if (false !== @lstat($target)) {
                        $this->acceptExisting($target, $validated->canonicalBytes());
                    } else {
                        throw InvocationPlanPublicationException::failed('atomic_publish_failed');
                    }
                }
            } catch (Throwable $caught) {
                $failure = $caught;
            }
        }

        if ((file_exists($temporary) || is_link($temporary)) && !@unlink($temporary)) {
            throw InvocationPlanPublicationException::failed('temporary_cleanup_failed', $failure);
        }
        if (null !== $failure) {
            throw $failure;
        }

        return $target;
    }

    private function temporaryPath(string $directory, string $sha256): string
    {
        try {
            $suffix = bin2hex(random_bytes(16));
        } catch (Throwable $failure) {
            throw InvocationPlanPublicationException::failed('temporary_create_failed', $failure);
        }

        return $directory . '/.kongono-model-invocation-plan-' . $sha256 . '-' . $suffix . '.tmp';
    }

    private function acceptExisting(string $target, string $expected): void
    {
        $resolved = realpath($target);
        if (false === $resolved || str_replace('\\', '/', $resolved) !== $target || is_link($target) || !is_file($target)) {
            throw InvocationPlanPublicationException::failed('unsafe_existing_target');
        }
        $before = @stat($target);
        if (false === $before || $before['size'] > self::MAX_ARTIFACT_BYTES) {
            throw InvocationPlanPublicationException::failed('existing_target_unreadable');
        }
        $handle = @fopen($target, 'rb');
        if (false === $handle) {
            throw InvocationPlanPublicationException::failed('existing_target_unreadable');
        }
        $bytes = '';
        try {
            while (!feof($handle)) {
                $remaining = self::MAX_ARTIFACT_BYTES + 1 - strlen($bytes);
                $chunk = @fread($handle, max(1, min(8192, $remaining)));
                if (false === $chunk) {
                    throw InvocationPlanPublicationException::failed('existing_target_unreadable');
                }
                $bytes .= $chunk;
                if (strlen($bytes) > self::MAX_ARTIFACT_BYTES) {
                    throw InvocationPlanPublicationException::failed('existing_target_conflict');
                }
            }
            $after = @fstat($handle);
        } finally {
            @fclose($handle);
        }
        if (false === $after || !$this->sameMetadata($before, $after) || strlen($bytes) !== $before['size']) {
            throw InvocationPlanPublicationException::failed('existing_target_changed');
        }
        if (!hash_equals($expected, $bytes)) {
            throw InvocationPlanPublicationException::failed('existing_target_conflict');
        }
        try {
            (new InvocationPlanArtifactValidator())->validateStructure($bytes);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw InvocationPlanPublicationException::failed('existing_target_invalid', $failure);
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
