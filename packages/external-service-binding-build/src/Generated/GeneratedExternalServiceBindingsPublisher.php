<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceBindingBuild\Generated;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceBindingBuild\Exception\GeneratedExternalServiceBindingsPublicationException;
use Kongono\ExternalServiceBindingBuild\Internal\CanonicalJson;
use Kongono\ExternalServiceBindingBuild\Internal\PathGuard;
use Throwable;

/** Publishes validated generated PHP atomically without loading or overwriting it. */
#[Api(ApiStatus::Experimental)]
final class GeneratedExternalServiceBindingsPublisher
{
    public function publish(GeneratedExternalServiceBindings $generated, string $outputDirectory): string
    {
        try {
            $validated = (new GeneratedExternalServiceBindingsValidator())->validateStructure($generated->canonicalBytes());
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw GeneratedExternalServiceBindingsPublicationException::failed('invalid_generated_php', $failure);
        }
        if (
            !hash_equals($validated->generatedPhpSha256(), $generated->generatedPhpSha256())
            || !$validated->sourceIdentity()->equals($generated->sourceIdentity())
            || !hash_equals($validated->sourceSha256(), $generated->sourceSha256())
            || !hash_equals($validated->bindingArtifactSha256(), $generated->bindingArtifactSha256())
            || !hash_equals($validated->generatorImplementationSha256(), $generated->generatorImplementationSha256())
            || !$this->bindingsEqual($validated->bindings(), $generated->bindings())
        ) {
            throw GeneratedExternalServiceBindingsPublicationException::failed('generated_identity_mismatch');
        }
        $directory = PathGuard::publicationDirectory($outputDirectory);
        if (null === $directory) {
            throw GeneratedExternalServiceBindingsPublicationException::failed('invalid_output_directory');
        }
        $target = $directory . '/kongono-external-service-bindings-v1-' . $validated->generatedPhpSha256() . '.php';
        if (false !== @lstat($target)) {
            $this->acceptExisting($target, $validated->canonicalBytes());

            return $target;
        }

        $temporary = $this->temporaryPath($directory, $validated->generatedPhpSha256());
        $handle = @fopen($temporary, 'xb');
        if (false === $handle) {
            throw GeneratedExternalServiceBindingsPublicationException::failed('temporary_open_failed');
        }
        $failure = null;
        try {
            if (is_link($temporary) || !is_file($temporary) || !str_starts_with($temporary, $directory . '/')) {
                throw GeneratedExternalServiceBindingsPublicationException::failed('unsafe_temporary_file');
            }
            $this->write($handle, $validated->canonicalBytes());
        } catch (Throwable $caught) {
            $failure = $caught;
        } finally {
            if (!@fclose($handle) && null === $failure) {
                $failure = GeneratedExternalServiceBindingsPublicationException::failed('temporary_sync_failed');
            }
        }
        if (null === $failure) {
            try {
                if (PathGuard::publicationDirectory($outputDirectory) !== $directory) {
                    throw GeneratedExternalServiceBindingsPublicationException::failed('path_changed');
                }
                if (!@link($temporary, $target)) {
                    clearstatcache(true, $target);
                    if (file_exists($target) || is_link($target)) {
                        $this->acceptExisting($target, $validated->canonicalBytes());
                    } else {
                        throw GeneratedExternalServiceBindingsPublicationException::failed('atomic_publish_failed');
                    }
                }
                $this->acceptExisting($target, $validated->canonicalBytes());
            } catch (Throwable $caught) {
                $failure = $caught;
            }
        }
        if ((file_exists($temporary) || is_link($temporary)) && !@unlink($temporary)) {
            throw GeneratedExternalServiceBindingsPublicationException::failed('temporary_cleanup_failed', $failure);
        }
        if (null !== $failure) {
            throw $failure;
        }

        return $target;
    }

    /** @param resource $handle */
    private function write($handle, string $bytes): void
    {
        $offset = 0;
        while ($offset < strlen($bytes)) {
            $written = @fwrite($handle, substr($bytes, $offset));
            if (false === $written || 0 === $written) {
                throw GeneratedExternalServiceBindingsPublicationException::failed('temporary_write_failed');
            }
            $offset += $written;
        }
        if (!@fflush($handle) || (function_exists('fsync') && !@fsync($handle))) {
            throw GeneratedExternalServiceBindingsPublicationException::failed('temporary_sync_failed');
        }
        $stat = @fstat($handle);
        if (false === $stat || $stat['size'] !== strlen($bytes)) {
            throw GeneratedExternalServiceBindingsPublicationException::failed('temporary_write_failed');
        }
    }

    private function temporaryPath(string $directory, string $sha256): string
    {
        try {
            return $directory . '/.kongono-external-service-bindings-' . $sha256 . '-' . bin2hex(random_bytes(16)) . '.tmp';
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw GeneratedExternalServiceBindingsPublicationException::failed('temporary_create_failed', $failure);
        }
    }

    private function acceptExisting(string $target, string $expected): void
    {
        $resolved = realpath($target);
        if (false === $resolved || str_replace('\\', '/', $resolved) !== $target || is_link($target) || !is_file($target)) {
            throw GeneratedExternalServiceBindingsPublicationException::failed('unsafe_existing_target');
        }
        $before = @stat($target);
        if (false === $before || $before['size'] > CanonicalJson::MAX_GENERATED_BYTES) {
            throw GeneratedExternalServiceBindingsPublicationException::failed('existing_target_unreadable');
        }
        $handle = @fopen($target, 'rb');
        if (false === $handle) {
            throw GeneratedExternalServiceBindingsPublicationException::failed('existing_target_unreadable');
        }
        try {
            $bytes = stream_get_contents($handle, CanonicalJson::MAX_GENERATED_BYTES + 1);
            $after = fstat($handle);
        } finally {
            fclose($handle);
        }
        if (false === $bytes || false === $after || !$this->sameMetadata($before, $after) || strlen($bytes) !== $before['size']) {
            throw GeneratedExternalServiceBindingsPublicationException::failed('existing_target_changed');
        }
        if (!hash_equals($expected, $bytes)) {
            throw GeneratedExternalServiceBindingsPublicationException::failed('existing_target_conflict');
        }
        try {
            (new GeneratedExternalServiceBindingsValidator())->validateStructure($bytes);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw GeneratedExternalServiceBindingsPublicationException::failed('existing_target_invalid', $failure);
        }
    }

    /** @param list<\Kongono\ExternalServiceContract\Binding\ServiceBinding> $left
     * @param list<\Kongono\ExternalServiceContract\Binding\ServiceBinding> $right
     */
    private function bindingsEqual(array $left, array $right): bool
    {
        if (count($left) !== count($right)) {
            return false;
        }
        foreach ($left as $position => $binding) {
            if (!$binding->equals($right[$position])) {
                return false;
            }
        }

        return true;
    }

    /** @param array<string|int, mixed> $before
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
