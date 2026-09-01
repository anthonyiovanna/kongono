<?php

declare(strict_types=1);

namespace Kongono\AgentDeclarationBuild\Artifact;

use Kongono\AgentDeclarationBuild\Exception\AgentDeclarationPublicationException;
use Kongono\AgentDeclarationBuild\Internal\PathGuard;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Publishes one validated content-addressed declaration artifact without overwrite. */
#[Api(ApiStatus::Experimental)]
final class AgentDeclarationArtifactPublisher
{
    private const int MAX_ARTIFACT_BYTES = 131_072;

    public function publish(AgentDeclarationArtifact $artifact, string $outputDirectory): string
    {
        try {
            $validated = (new AgentDeclarationArtifactValidator())->validateStructure($artifact->canonicalBytes());
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw AgentDeclarationPublicationException::failed('invalid_artifact', $failure);
        }
        if (!hash_equals($validated->artifactSha256(), $artifact->artifactSha256())) {
            throw AgentDeclarationPublicationException::failed('artifact_identity_mismatch');
        }

        $directory = PathGuard::publicationDirectory($outputDirectory);
        $target = $directory . '/kongono-agent-declaration-v1-' . $validated->artifactSha256() . '.json';
        if (file_exists($target) || is_link($target)) {
            $this->acceptExisting($target, $validated->canonicalBytes());

            return $target;
        }

        $temporary = $this->temporaryPath($directory, $validated->artifactSha256());
        $handle = @fopen($temporary, 'xb');
        if (false === $handle) {
            throw AgentDeclarationPublicationException::failed('temporary_open_failed');
        }

        $failure = null;
        try {
            if (is_link($temporary) || !is_file($temporary) || !str_starts_with($temporary, $directory . '/')) {
                throw AgentDeclarationPublicationException::failed('unsafe_temporary_file');
            }
            $bytes = $validated->canonicalBytes();
            $offset = 0;
            while ($offset < strlen($bytes)) {
                $written = @fwrite($handle, substr($bytes, $offset));
                if (false === $written || 0 === $written) {
                    throw AgentDeclarationPublicationException::failed('temporary_write_failed');
                }
                $offset += $written;
            }
            if (!@fflush($handle) || (function_exists('fsync') && !@fsync($handle))) {
                throw AgentDeclarationPublicationException::failed('temporary_sync_failed');
            }
            $stat = @fstat($handle);
            if (false === $stat || $stat['size'] !== strlen($bytes)) {
                throw AgentDeclarationPublicationException::failed('temporary_write_failed');
            }
        } catch (Throwable $caught) {
            $failure = $caught;
        } finally {
            if (!@fclose($handle) && null === $failure) {
                $failure = AgentDeclarationPublicationException::failed('temporary_sync_failed');
            }
        }

        if (null === $failure) {
            try {
                if (PathGuard::publicationDirectory($outputDirectory) !== $directory) {
                    throw AgentDeclarationPublicationException::failed('link_or_reparse');
                }
                if (!@link($temporary, $target)) {
                    if (false !== @lstat($target)) {
                        $this->acceptExisting($target, $validated->canonicalBytes());
                    } else {
                        throw AgentDeclarationPublicationException::failed('atomic_publish_failed');
                    }
                }
            } catch (Throwable $caught) {
                $failure = $caught;
            }
        }

        if ((file_exists($temporary) || is_link($temporary)) && !@unlink($temporary)) {
            throw AgentDeclarationPublicationException::failed('temporary_cleanup_failed', $failure);
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
            throw AgentDeclarationPublicationException::failed('temporary_create_failed', $failure);
        }

        return $directory . '/.kongono-agent-declaration-' . $sha256 . '-' . $suffix . '.tmp';
    }

    private function acceptExisting(string $target, string $expected): void
    {
        $resolved = realpath($target);
        if (false === $resolved || str_replace('\\', '/', $resolved) !== $target || is_link($target) || !is_file($target)) {
            throw AgentDeclarationPublicationException::failed('unsafe_existing_target');
        }
        $before = @stat($target);
        if (false === $before || $before['size'] > self::MAX_ARTIFACT_BYTES) {
            throw AgentDeclarationPublicationException::failed('existing_target_unreadable');
        }
        $handle = @fopen($target, 'rb');
        if (false === $handle) {
            throw AgentDeclarationPublicationException::failed('existing_target_unreadable');
        }
        $bytes = '';
        try {
            while (!feof($handle)) {
                $remaining = self::MAX_ARTIFACT_BYTES + 1 - strlen($bytes);
                $chunk = @fread($handle, max(1, min(8192, $remaining)));
                if (false === $chunk) {
                    throw AgentDeclarationPublicationException::failed('existing_target_unreadable');
                }
                $bytes .= $chunk;
                if (strlen($bytes) > self::MAX_ARTIFACT_BYTES) {
                    throw AgentDeclarationPublicationException::failed('existing_target_conflict');
                }
            }
            $after = @fstat($handle);
        } finally {
            @fclose($handle);
        }
        if (false === $after || !$this->sameMetadata($before, $after) || strlen($bytes) !== $before['size']) {
            throw AgentDeclarationPublicationException::failed('existing_target_changed');
        }
        if (!hash_equals($expected, $bytes)) {
            throw AgentDeclarationPublicationException::failed('existing_target_conflict');
        }
        try {
            (new AgentDeclarationArtifactValidator())->validateStructure($bytes);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw AgentDeclarationPublicationException::failed('existing_target_invalid', $failure);
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
