<?php

declare(strict_types=1);

namespace Kongono\Tests\External\Asset;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

final class TemporaryAssetWorkspace
{
    private string $root;
    private string $temporaryRoot;

    public function __construct(string $repositoryRoot)
    {
        $temporary = realpath(sys_get_temp_dir());
        $repository = realpath($repositoryRoot);
        if (false === $temporary || false === $repository) {
            throw new RuntimeException('phase19_workspace_failed');
        }
        $this->temporaryRoot = self::normalize($temporary);
        $repository = self::normalize($repository);
        $this->root = $this->temporaryRoot . '/kongono-phase19-' . bin2hex(random_bytes(16));
        if (self::isWithin($this->root, $repository) || file_exists($this->root) || is_link($this->root) || !mkdir($this->root, 0700)) {
            throw new RuntimeException('phase19_workspace_failed');
        }
        $this->assertDirectory($this->root, true);
    }

    public function root(): string
    {
        return $this->root;
    }

    public function directory(string $relative): string
    {
        $path = $this->path($relative);
        if ((!is_dir($path) && !mkdir($path, 0700, true)) || is_link($path)) {
            throw new RuntimeException('phase19_workspace_failed');
        }
        $this->assertDirectory($path, false);

        return $path;
    }

    public function write(string $relative, string $bytes): string
    {
        $path = $this->path($relative);
        $parent = str_replace('\\', '/', dirname($relative));
        if ('.' !== $parent) {
            $this->directory($parent);
        }
        if (strlen($bytes) !== file_put_contents($path, $bytes, LOCK_EX)) {
            throw new RuntimeException('phase19_workspace_failed');
        }
        @chmod($path, 0600);
        $this->assertOrdinaryFile($path);

        return $path;
    }

    public function path(string $relative): string
    {
        $relative = str_replace('\\', '/', $relative);
        if ('' === $relative || str_starts_with($relative, '/') || str_contains($relative, "\0")) {
            throw new RuntimeException('phase19_workspace_failed');
        }
        foreach (explode('/', $relative) as $segment) {
            if ('' === $segment || '.' === $segment || '..' === $segment || 1 !== preg_match('/\A[A-Za-z0-9._-]+\z/D', $segment)) {
                throw new RuntimeException('phase19_workspace_failed');
            }
        }

        return $this->root . '/' . $relative;
    }

    public function copyTree(string $source, string $relativeDestination): string
    {
        $resolved = realpath($source);
        if (false === $resolved || !is_dir($resolved) || is_link($source) || self::isReparsePoint($source)) {
            throw new RuntimeException('phase19_workspace_failed');
        }
        $source = self::normalize($resolved);
        $destination = $this->directory($relativeDestination);
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );
        foreach ($items as $entry) {
            if (!$entry instanceof SplFileInfo || $entry->isLink() || self::isReparsePoint($entry->getPathname())) {
                throw new RuntimeException('phase19_workspace_failed');
            }
            $relative = substr(self::normalize($entry->getPathname()), strlen($source) + 1);
            $target = $destination . '/' . $relative;
            if ($entry->isDir()) {
                if (!mkdir($target, 0700, true) && !is_dir($target)) {
                    throw new RuntimeException('phase19_workspace_failed');
                }
            } elseif (!$entry->isFile() || !copy($entry->getPathname(), $target)) {
                throw new RuntimeException('phase19_workspace_failed');
            }
        }

        return $destination;
    }

    public function cleanup(): void
    {
        if (!file_exists($this->root) && !is_link($this->root)) {
            return;
        }
        $this->assertDirectory($this->root, true);
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($items as $entry) {
            if (!$entry instanceof SplFileInfo) {
                throw new RuntimeException('phase19_workspace_failed');
            }
            $path = self::normalize($entry->getPathname());
            $this->assertContained($path);
            if ($entry->isLink() || self::isReparsePoint($path)) {
                if (!unlink($path)) {
                    throw new RuntimeException('phase19_workspace_failed');
                }
            } elseif ($entry->isDir()) {
                $this->assertDirectory($path, false);
                if (!rmdir($path)) {
                    throw new RuntimeException('phase19_workspace_failed');
                }
            } elseif ('file' === $entry->getType()) {
                $this->assertOrdinaryFile($path);
                if (!unlink($path)) {
                    throw new RuntimeException('phase19_workspace_failed');
                }
            } else {
                throw new RuntimeException('phase19_workspace_failed');
            }
        }
        if (!rmdir($this->root)) {
            throw new RuntimeException('phase19_workspace_failed');
        }
    }

    private function assertDirectory(string $path, bool $directChild): void
    {
        $this->assertContained($path);
        if (!is_dir($path) || is_link($path) || self::isReparsePoint($path) || false === lstat($path)) {
            throw new RuntimeException('phase19_workspace_failed');
        }
        $resolved = realpath($path);
        if (false === $resolved || 0 !== strcasecmp(self::normalize($resolved), self::normalize($path))) {
            throw new RuntimeException('phase19_workspace_failed');
        }
        if ($directChild && 0 !== strcasecmp(self::normalize(dirname($path)), $this->temporaryRoot)) {
            throw new RuntimeException('phase19_workspace_failed');
        }
    }

    private function assertOrdinaryFile(string $path): void
    {
        $this->assertContained($path);
        if (!is_file($path) || is_link($path) || self::isReparsePoint($path) || false === lstat($path)) {
            throw new RuntimeException('phase19_workspace_failed');
        }
        $resolved = realpath($path);
        if (false === $resolved || 0 !== strcasecmp(self::normalize($resolved), self::normalize($path))) {
            throw new RuntimeException('phase19_workspace_failed');
        }
    }

    private function assertContained(string $path): void
    {
        if (!self::isWithin(self::normalize($path), $this->root)) {
            throw new RuntimeException('phase19_workspace_failed');
        }
    }

    private static function isWithin(string $path, string $root): bool
    {
        $path = strtolower(rtrim(self::normalize($path), '/'));
        $root = strtolower(rtrim(self::normalize($root), '/'));

        return $path === $root || str_starts_with($path, $root . '/');
    }

    private static function isReparsePoint(string $path): bool
    {
        $target = @readlink($path);

        return false !== $target && 0 !== strcasecmp(self::normalize($target), self::normalize($path));
    }

    private static function normalize(string $path): string
    {
        return rtrim(str_replace('\\', '/', $path), '/');
    }
}
