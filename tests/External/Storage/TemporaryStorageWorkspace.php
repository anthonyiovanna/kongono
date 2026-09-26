<?php

declare(strict_types=1);

namespace Kongono\Tests\External\Storage;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

/** Owns exactly one random direct child of the resolved OS temporary directory. */
final class TemporaryStorageWorkspace
{
    private string $root;
    private string $temporaryRoot;
    /** @var array<string, 'file'|'directory'> */
    private array $ownedEntries = [];
    /** @var array<string, string> */
    private array $interferenceFixtures = [];

    public function __construct(string $repositoryRoot)
    {
        $temporary = realpath(sys_get_temp_dir());
        $repository = realpath($repositoryRoot);
        if (false === $temporary || false === $repository || !is_dir($temporary) || is_link($temporary)) {
            throw new RuntimeException('phase21_workspace_failed');
        }
        $this->temporaryRoot = self::normalize($temporary);
        $repository = self::normalize($repository);
        $this->root = $this->temporaryRoot . '/kongono-phase21h-' . bin2hex(random_bytes(16));
        if (self::within($this->root, $repository) || file_exists($this->root) || is_link($this->root) || !mkdir($this->root, 0700)) {
            throw new RuntimeException('phase21_workspace_failed');
        }
        $this->assertDirectory($this->root);
        $this->ownedEntries[$this->root] = 'directory';
        $this->directory('work');
        $this->directory('capture');
    }

    public function root(): string
    {
        return $this->root;
    }

    public function path(string $relative): string
    {
        $relative = str_replace('\\', '/', $relative);
        if ('' === $relative || str_starts_with($relative, '/') || str_contains($relative, "\0")) {
            throw new RuntimeException('phase21_workspace_failed');
        }
        foreach (explode('/', $relative) as $segment) {
            if ('' === $segment || '.' === $segment || '..' === $segment || 1 !== preg_match('/\A[A-Za-z0-9._-]+\z/D', $segment)) {
                throw new RuntimeException('phase21_workspace_failed');
            }
        }

        return $this->root . '/' . $relative;
    }

    public function directory(string $relative): string
    {
        $path = $this->path($relative);
        $cursor = $this->root;
        foreach (explode('/', str_replace('\\', '/', $relative)) as $segment) {
            $cursor .= '/' . $segment;
            if (!is_dir($cursor) && !mkdir($cursor, 0700)) {
                throw new RuntimeException('phase21_workspace_failed');
            }
            $this->assertDirectory($cursor);
            $this->ownedEntries[$cursor] = 'directory';
        }

        return $path;
    }

    public function write(string $relative, string $bytes): string
    {
        $path = $this->path($relative);
        $parent = dirname($relative);
        if ('.' !== $parent) {
            $this->directory($parent);
        }
        if (file_exists($path) || is_link($path) || strlen($bytes) !== file_put_contents($path, $bytes, LOCK_EX)) {
            throw new RuntimeException('phase21_workspace_failed');
        }
        $this->assertFile($path);
        $this->ownedEntries[$path] = 'file';

        return $path;
    }

    public function copyTree(string $source, string $destination): string
    {
        $resolved = realpath($source);
        if (false === $resolved || !is_dir($resolved) || is_link($source) || self::reparse($source)) {
            throw new RuntimeException('phase21_workspace_failed');
        }
        $base = self::normalize($resolved);
        $target = $this->directory($destination);
        $entries = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($entries as $entry) {
            if (!$entry instanceof SplFileInfo || $entry->isLink() || self::reparse($entry->getPathname())) {
                throw new RuntimeException('phase21_workspace_failed');
            }
            $relative = substr(self::normalize($entry->getPathname()), strlen($base) + 1);
            $owned = $destination . '/' . $relative;
            if ($entry->isDir()) {
                $this->directory($owned);
            } elseif ($entry->isFile()) {
                $bytes = file_get_contents($entry->getPathname());
                if (false === $bytes) {
                    throw new RuntimeException('phase21_workspace_failed');
                }
                $this->write($owned, $bytes);
            } else {
                throw new RuntimeException('phase21_workspace_failed');
            }
        }

        return $target;
    }

    public function assertFile(string $path): void
    {
        $this->assertContained($path);
        if (!is_file($path) || is_link($path) || self::reparse($path) || false === lstat($path)) {
            throw new RuntimeException('phase21_workspace_failed');
        }
        $resolved = realpath($path);
        if (false === $resolved || 0 !== strcasecmp(self::normalize($resolved), self::normalize($path))) {
            throw new RuntimeException('phase21_workspace_failed');
        }
    }

    public function exclusiveFile(string $relative): string
    {
        $path = $this->path($relative);
        $this->directory(dirname($relative));
        if (file_exists($path) || is_link($path)) {
            throw new RuntimeException('phase21_workspace_failed');
        }
        $handle = fopen($path, 'xb');
        if (false === $handle) {
            throw new RuntimeException('phase21_workspace_failed');
        }
        fclose($handle);
        $this->assertFile($path);
        $this->ownedEntries[$path] = 'file';

        return $path;
    }

    public function registerProducedTree(string $relative): void
    {
        $root = $this->path($relative);
        $this->assertDirectory($root);
        $entries = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($entries as $entry) {
            if (!$entry instanceof SplFileInfo) {
                throw new RuntimeException('phase21_workspace_failed');
            }
            $path = self::normalize($entry->getPathname());
            if ($entry->isDir()) {
                $this->assertDirectory($path);
                $this->ownedEntries[$path] = 'directory';
            } elseif ($entry->isFile()) {
                $this->assertFile($path);
                $this->ownedEntries[$path] = 'file';
            } else {
                throw new RuntimeException('phase21_workspace_failed');
            }
        }
    }

    public function createInterferenceFixture(string $relative, string $bytes): string
    {
        $path = $this->path($relative);
        if (!str_starts_with($relative, 'capture/self-test-') || isset($this->ownedEntries[$path]) || isset($this->interferenceFixtures[$path])) {
            throw new RuntimeException('phase21_workspace_failed');
        }
        $this->directory(dirname($relative));
        $handle = fopen($path, 'xb');
        if (false === $handle || strlen($bytes) !== fwrite($handle, $bytes)) {
            throw new RuntimeException('phase21_workspace_failed');
        }
        fclose($handle);
        $this->assertFile($path);
        $this->interferenceFixtures[$path] = hash('sha256', $bytes);

        return $path;
    }

    public function removeInterferenceFixture(string $relative): void
    {
        $path = $this->path($relative);
        if (!isset($this->interferenceFixtures[$path])) {
            throw new RuntimeException('phase21_workspace_failed');
        }
        $this->assertFile($path);
        if (hash_file('sha256', $path) !== $this->interferenceFixtures[$path] || !unlink($path)) {
            throw new RuntimeException('phase21_workspace_failed');
        }
        unset($this->interferenceFixtures[$path]);
    }

    public function cleanup(): void
    {
        $this->assertDirectory($this->root);
        $entries = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        /** @var list<array{string, 'file'|'directory'}> $deletions */
        $deletions = [];
        foreach ($entries as $entry) {
            if (!$entry instanceof SplFileInfo) {
                throw new RuntimeException('phase21_workspace_failed');
            }
            $path = self::normalize($entry->getPathname());
            $this->assertContained($path);
            if ($entry->isLink() || self::reparse($path)) {
                throw new RuntimeException('phase21_workspace_failed');
            }
            if ($entry->isDir()) {
                $this->assertDirectory($path);
                $kind = 'directory';
            } elseif ($entry->isFile()) {
                $this->assertFile($path);
                $kind = 'file';
            } else {
                throw new RuntimeException('phase21_workspace_failed');
            }
            if (($this->ownedEntries[$path] ?? null) !== $kind) {
                throw new RuntimeException('phase21_workspace_failed');
            }
            $deletions[] = [$path, $kind];
        }
        foreach ($deletions as [$path, $kind]) {
            if ('directory' === $kind ? !rmdir($path) : !unlink($path)) {
                throw new RuntimeException('phase21_workspace_failed');
            }
        }
        if (!rmdir($this->root)) {
            throw new RuntimeException('phase21_workspace_failed');
        }
    }

    private function assertDirectory(string $path): void
    {
        $this->assertContained($path);
        if (!is_dir($path) || is_link($path) || self::reparse($path) || false === lstat($path)) {
            throw new RuntimeException('phase21_workspace_failed');
        }
        $resolved = realpath($path);
        if (false === $resolved || 0 !== strcasecmp(self::normalize($resolved), self::normalize($path))) {
            throw new RuntimeException('phase21_workspace_failed');
        }
    }

    private function assertContained(string $path): void
    {
        if (!self::within(self::normalize($path), $this->root) || self::within($path, $this->temporaryRoot) && $path === $this->temporaryRoot) {
            throw new RuntimeException('phase21_workspace_failed');
        }
    }

    private static function within(string $path, string $root): bool
    {
        $path = strtolower(rtrim(self::normalize($path), '/'));
        $root = strtolower(rtrim(self::normalize($root), '/'));

        return $path === $root || str_starts_with($path, $root . '/');
    }

    private static function reparse(string $path): bool
    {
        $target = @readlink($path);

        return false !== $target && 0 !== strcasecmp(self::normalize($target), self::normalize($path));
    }

    private static function normalize(string $path): string
    {
        return rtrim(str_replace('\\', '/', $path), '/');
    }
}
