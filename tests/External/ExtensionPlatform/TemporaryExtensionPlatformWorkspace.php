<?php

declare(strict_types=1);

namespace Kongono\Tests\External\ExtensionPlatform;

use RuntimeException;

final class TemporaryExtensionPlatformWorkspace
{
    private ?string $root = null;

    public function create(string $repositoryRoot): void
    {
        if (null !== $this->root) {
            throw new RuntimeException('workspace_already_created');
        }

        $temporaryRoot = realpath(sys_get_temp_dir());
        if (false === $temporaryRoot) {
            throw new RuntimeException('temporary_root_unavailable');
        }

        $candidate = str_replace('\\', '/', $temporaryRoot) . '/kongono phase12 extension-' . bin2hex(random_bytes(12));
        if (str_starts_with(strtolower($candidate . '/'), strtolower(str_replace('\\', '/', $repositoryRoot) . '/'))) {
            throw new RuntimeException('workspace_overlaps_repository');
        }
        if (!mkdir($candidate, 0700)) {
            throw new RuntimeException('workspace_create_failed');
        }

        $resolved = realpath($candidate);
        if (false === $resolved || $this->isLinkLike($resolved)) {
            throw new RuntimeException('workspace_unsafe');
        }
        $this->root = str_replace('\\', '/', $resolved);
        foreach (['declaration', 'wiring', 'conflict', 'children'] as $directory) {
            $this->makeDirectory($directory);
        }
    }

    public function path(string $relative = ''): string
    {
        $root = $this->requiredRoot();
        if ('' === $relative) {
            return $root;
        }
        if (str_contains($relative, "\0") || str_contains($relative, '..') || str_starts_with($relative, '/') || str_contains($relative, '\\')) {
            throw new RuntimeException('unsafe_relative_path');
        }

        return $root . '/' . $relative;
    }

    public function makeDirectory(string $relative): string
    {
        $path = $this->path($relative);
        if (!is_dir($path) && !mkdir($path, 0700, true)) {
            throw new RuntimeException('directory_create_failed');
        }

        return $path;
    }

    public function write(string $relative, string $bytes): string
    {
        $path = $this->path($relative);
        $parent = dirname($path);
        if (!is_dir($parent) && !mkdir($parent, 0700, true)) {
            throw new RuntimeException('parent_create_failed');
        }
        if (strlen($bytes) !== file_put_contents($path, $bytes, LOCK_EX)) {
            throw new RuntimeException('file_write_failed');
        }

        return $path;
    }

    public function identity(): string
    {
        $root = $this->requiredRoot();
        $records = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );
        foreach ($iterator as $entry) {
            if (!$entry instanceof \SplFileInfo) {
                throw new RuntimeException('workspace_entry_invalid');
            }
            $path = str_replace('\\', '/', $entry->getPathname());
            if ($entry->isLink() || $this->isLinkLike($path)) {
                throw new RuntimeException('workspace_link_detected');
            }
            $relative = substr($path, strlen($root) + 1);
            if ($entry->isDir()) {
                $records[] = 'd|' . $relative;
                continue;
            }
            $digest = hash_file('sha256', $path);
            if (false === $digest) {
                throw new RuntimeException('workspace_digest_failed');
            }
            $records[] = 'f|' . $relative . '|' . $entry->getSize() . '|' . strtoupper($digest);
        }
        sort($records, SORT_STRING);

        return strtoupper(hash('sha256', implode("\n", $records)));
    }

    public function cleanup(): void
    {
        if (null === $this->root) {
            return;
        }
        $root = $this->root;
        if (!is_dir($root) || $this->isLinkLike($root)) {
            throw new RuntimeException('cleanup_root_unsafe');
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $entry) {
            if (!$entry instanceof \SplFileInfo) {
                throw new RuntimeException('cleanup_entry_invalid');
            }
            $path = $entry->getPathname();
            if ($entry->isLink() || $this->isLinkLike($path)) {
                throw new RuntimeException('cleanup_link_detected');
            }
            $removed = $entry->isDir() ? rmdir($path) : unlink($path);
            if (!$removed) {
                throw new RuntimeException('cleanup_failed');
            }
        }
        if (!rmdir($root)) {
            throw new RuntimeException('cleanup_root_failed');
        }
        $this->root = null;
        if (file_exists($root)) {
            throw new RuntimeException('cleanup_residue');
        }
    }

    private function requiredRoot(): string
    {
        if (null === $this->root) {
            throw new RuntimeException('workspace_not_created');
        }

        return $this->root;
    }

    private function isLinkLike(string $path): bool
    {
        if (is_link($path)) {
            return true;
        }
        if ('Windows' !== PHP_OS_FAMILY || !file_exists($path)) {
            return false;
        }
        $attributes = @fileperms($path);

        return false === $attributes;
    }
}
