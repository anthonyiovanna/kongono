<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterVite\Workspace;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetAdapterVite\Exception\ViteWorkspaceException;

/** Carries and removes one operation-owned contained Vite workspace. */
#[Api(ApiStatus::Internal)]final readonly class ViteWorkspace
{
    public function __construct(private string $root, private string $sourceRoot, private string $outputRoot, private string $configPath) {}
    public function root(): string
    {
        return$this->root;
    }public function sourceRoot(): string
    {
        return$this->sourceRoot;
    }public function outputRoot(): string
    {
        return$this->outputRoot;
    }public function configPath(): string
    {
        return$this->configPath;
    }
    public function cleanup(): void
    {
        if (!$this->removeDirectory($this->root) || file_exists($this->root) || is_link($this->root)) {
            throw ViteWorkspaceException::failed('cleanup_failed', cleanupStatus: 'failed');
        }
    }
    private function removeDirectory(string $directory): bool
    {
        if (is_link($directory)) {
            return @unlink($directory);
        }if (!is_dir($directory)) {
            return !file_exists($directory);
        }$entries = @scandir($directory);
        if (false === $entries) {
            return false;
        }foreach ($entries as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }$path = $directory . '/' . $entry;
            if (is_link($path)) {
                if (!@unlink($path)) {
                    return false;
                }
            } elseif (is_dir($path)) {
                if (!$this->removeDirectory($path)) {
                    return false;
                }
            } elseif (is_file($path)) {
                if (!@unlink($path)) {
                    return false;
                }
            } else {
                return false;
            }
        }return @rmdir($directory);
    }
}
