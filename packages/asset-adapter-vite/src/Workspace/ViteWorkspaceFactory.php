<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterVite\Workspace;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetAdapterVite\Exception\ViteWorkspaceException;
use Kongono\AssetAdapterVite\Internal\FixedViteConfig;
use Kongono\AssetAdapterVite\Internal\PathGuard;
use Kongono\AssetBuild\Source\AssetSourceSet;
use Throwable;

/** Creates one fresh contained workspace with exact source and config bytes. */
#[Api(ApiStatus::Internal)]final class ViteWorkspaceFactory
{
    public function __construct(private readonly string $workspaceParent) {}
    public function create(AssetSourceSet $sourceSet): ViteWorkspace
    {
        $parent = PathGuard::existingDirectory($this->workspaceParent);
        try {
            $root = $parent . '/.kongono-vite-' . bin2hex(random_bytes(16));
        } catch (Throwable $failure) {
            throw ViteWorkspaceException::failed('create_failed', $failure);
        }if (file_exists($root) || is_link($root)) {
            throw ViteWorkspaceException::failed('operation_collision');
        }if (!@mkdir($root, 0700)) {
            throw ViteWorkspaceException::failed('create_failed');
        }$workspace = new ViteWorkspace($root, $root . '/source', $root . '/output', $root . '/vite.config.mjs');
        try {
            if (!@mkdir($workspace->sourceRoot(), 0700) || !@mkdir($workspace->outputRoot(), 0700)) {
                throw ViteWorkspaceException::failed('create_failed');
            }foreach ($sourceSet->sources() as $source) {
                $relative = PathGuard::relative($source->path()->value());
                $path = $workspace->sourceRoot() . '/' . $relative;
                $this->parents($workspace->sourceRoot(), dirname($relative));
                $this->write($path, $source->bytes());
                if (!hash_equals($source->bytes(), $this->readExact($path, $source->byteCount()))) {
                    throw ViteWorkspaceException::failed('source_changed');
                }
            }$this->write($workspace->configPath(), FixedViteConfig::bytes($sourceSet, $workspace->sourceRoot(), $workspace->outputRoot()));
            return$workspace;
        } catch (Throwable $failure) {
            try {
                $workspace->cleanup();
            } catch (Throwable $cleanup) {
                throw ViteWorkspaceException::failed('cleanup_failed', $failure, 'failed');
            }if ($failure instanceof \Error || $failure instanceof ViteWorkspaceException) {
                throw$failure;
            }throw ViteWorkspaceException::failed('write_failed', $failure, 'complete');
        }
    }
    private function parents(string $root, string $relative): void
    {
        if ('.' === $relative) {
            return;
        }$current = $root;
        foreach (explode('/', $relative) as $segment) {
            $current .= '/' . $segment;
            if (is_dir($current) && !is_link($current)) {
                continue;
            }if (file_exists($current) || is_link($current) || !@mkdir($current, 0700)) {
                throw ViteWorkspaceException::failed('create_failed');
            }
        }
    }
    private function write(string $path, string $bytes): void
    {
        $handle = @fopen($path, 'xb');
        if (false === $handle) {
            throw ViteWorkspaceException::failed('write_failed');
        }$failure = null;
        try {
            $offset = 0;
            while ($offset < strlen($bytes)) {
                $written = @fwrite($handle, substr($bytes, $offset, 8192));
                if (false === $written || 0 === $written) {
                    throw ViteWorkspaceException::failed('write_failed');
                }$offset += $written;
            }if (!@fflush($handle)) {
                throw ViteWorkspaceException::failed('write_failed');
            }$stat = @fstat($handle);
            if (false === $stat || $stat['size'] !== strlen($bytes)) {
                throw ViteWorkspaceException::failed('write_failed');
            }
        } catch (Throwable $caught) {
            $failure = $caught;
        } finally {
            if (!@fclose($handle) && null === $failure) {
                $failure = ViteWorkspaceException::failed('write_failed');
            }
        }if (null !== $failure) {
            throw$failure;
        }
    }
    private function readExact(string $path, int $size): string
    {
        $bytes = @file_get_contents($path);
        if (false === $bytes || strlen($bytes) !== $size) {
            throw ViteWorkspaceException::failed('source_changed');
        }return$bytes;
    }
}
