<?php

declare(strict_types=1);

namespace Kongono\Tests\External\AgentPrincipal;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

final class TemporaryAgentPrincipalWorkspace
{
    private ?string $root = null;
    private ?string $removedRoot = null;

    public function create(string $repositoryRoot): void
    {
        if (null !== $this->root) {
            throw new RuntimeException('workspace_already_created');
        }
        $temporaryRoot = realpath(sys_get_temp_dir());
        if (false === $temporaryRoot || self::linkLike($temporaryRoot)) {
            throw new RuntimeException('temporary_root_unsafe');
        }
        $candidate = self::normalize($temporaryRoot) . '/kongono phase13 agent-' . bin2hex(random_bytes(12));
        $repository = rtrim(self::normalize($repositoryRoot), '/');
        if (self::within($candidate, $repository) || self::within($repository, $candidate)) {
            throw new RuntimeException('workspace_overlaps_repository');
        }
        if (!mkdir($candidate, 0700)) {
            throw new RuntimeException('workspace_create_failed');
        }
        $resolved = realpath($candidate);
        if (false === $resolved || self::linkLike($candidate)) {
            throw new RuntimeException('workspace_unsafe');
        }
        $this->root = rtrim(self::normalize($resolved), '/');
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
        $candidate = self::normalize($root . '/' . $relative);
        if (!self::within($candidate, $root)) {
            throw new RuntimeException('temporary_path_escape');
        }

        return $candidate;
    }

    public function makeDirectory(string $relative): string
    {
        $path = $this->path($relative);
        if (!is_dir($path) && !mkdir($path, 0700, true)) {
            throw new RuntimeException('directory_create_failed');
        }
        $this->assertContainedAncestors($path);

        return $path;
    }

    public function write(string $relative, string $bytes): string
    {
        $path = $this->path($relative);
        $parent = dirname($path);
        if (!is_dir($parent) && !mkdir($parent, 0700, true)) {
            throw new RuntimeException('parent_create_failed');
        }
        $this->assertContainedAncestors($parent);
        if (strlen($bytes) !== file_put_contents($path, $bytes, LOCK_EX)) {
            throw new RuntimeException('file_write_failed');
        }
        $stat = lstat($path);
        if (false === $stat || self::linkLike($path) || 0100000 !== ($stat['mode'] & 0170000)) {
            throw new RuntimeException('ordinary_file_required');
        }

        return $path;
    }

    public function identity(): string
    {
        $root = $this->requiredRoot();
        $this->assertContainedAncestors($root);
        $records = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );
        foreach ($iterator as $entry) {
            if (!$entry instanceof SplFileInfo) {
                throw new RuntimeException('workspace_entry_invalid');
            }
            $path = self::normalize($entry->getPathname());
            if (self::linkLike($path)) {
                throw new RuntimeException('workspace_link_detected');
            }
            $this->assertContainedAncestors($path);
            $relative = substr($path, strlen($root) + 1);
            if ($entry->isDir()) {
                $records[] = 'd|' . $relative;
                continue;
            }
            $stat = lstat($path);
            $digest = hash_file('sha256', $path);
            if (false === $stat || 0100000 !== ($stat['mode'] & 0170000) || false === $digest) {
                throw new RuntimeException('workspace_file_invalid');
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
        if (!is_dir($root) || self::linkLike($root)) {
            throw new RuntimeException('cleanup_root_unsafe');
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $entry) {
            if (!$entry instanceof SplFileInfo) {
                throw new RuntimeException('cleanup_entry_invalid');
            }
            $path = self::normalize($entry->getPathname());
            if (self::linkLike($path)) {
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
        $this->removedRoot = $root;
        $this->root = null;
        if (file_exists($root) || is_link($root)) {
            throw new RuntimeException('cleanup_residue');
        }
    }

    public function cleanupComplete(): bool
    {
        return null === $this->root && null !== $this->removedRoot && !file_exists($this->removedRoot) && !is_link($this->removedRoot);
    }

    private function requiredRoot(): string
    {
        if (null === $this->root) {
            throw new RuntimeException('workspace_not_created');
        }

        return $this->root;
    }

    private function assertContainedAncestors(string $path): void
    {
        $root = $this->requiredRoot();
        $candidate = self::normalize($path);
        if ($candidate !== $root && !self::within($candidate, $root)) {
            throw new RuntimeException('temporary_path_escape');
        }
        while (strlen($candidate) >= strlen($root)) {
            if (file_exists($candidate) && self::linkLike($candidate)) {
                throw new RuntimeException('workspace_link_detected');
            }
            if ($candidate === $root) {
                return;
            }
            $candidate = self::normalize(dirname($candidate));
        }
        throw new RuntimeException('temporary_path_escape');
    }

    private static function linkLike(string $path): bool
    {
        if (is_link($path)) {
            return true;
        }
        $target = @readlink($path);

        return false !== $target && strtolower(self::normalize($target)) !== strtolower(self::normalize($path));
    }

    private static function within(string $candidate, string $root): bool
    {
        return str_starts_with(strtolower(rtrim($candidate, '/') . '/'), strtolower(rtrim($root, '/') . '/'));
    }

    private static function normalize(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}
