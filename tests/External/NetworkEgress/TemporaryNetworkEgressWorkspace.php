<?php

declare(strict_types=1);

namespace Kongono\Tests\External\NetworkEgress;

use RuntimeException;

final class TemporaryNetworkEgressWorkspace
{
    private string $root;
    private string $temporaryRoot;

    public function __construct(string $repositoryRoot)
    {
        $temporary = realpath(sys_get_temp_dir());
        $repository = realpath($repositoryRoot);
        if (false === $temporary || false === $repository) {
            throw new RuntimeException('phase15_workspace_failed');
        }
        $this->temporaryRoot = self::normalize($temporary);
        $repository = self::normalize($repository);
        $this->root = $this->temporaryRoot . '/kongono-phase15-' . bin2hex(random_bytes(16));
        if (self::isWithin($this->root, $repository) || !mkdir($this->root, 0700)) {
            throw new RuntimeException('phase15_workspace_failed');
        }
        $this->assertDirectory($this->root, true);
    }

    public function root(): string
    {
        return $this->root;
    }

    public function directory(string $name): string
    {
        if ('' === $name || str_contains($name, "\0") || str_contains($name, '/') || str_contains($name, '\\') || '.' === $name || '..' === $name) {
            throw new RuntimeException('phase15_workspace_failed');
        }
        $path = $this->root . '/' . $name;
        if (!mkdir($path, 0700)) {
            throw new RuntimeException('phase15_workspace_failed');
        }
        $this->assertDirectory($path, false);

        return $path;
    }

    public function cleanup(): void
    {
        if (!is_dir($this->root)) {
            return;
        }
        $this->assertDirectory($this->root, true);
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($items as $item) {
            if (!$item instanceof \SplFileInfo) {
                throw new RuntimeException('phase15_workspace_failed');
            }
            $path = self::normalize($item->getPathname());
            $this->assertContained($path);
            if ($item->isLink()) {
                throw new RuntimeException('phase15_workspace_failed');
            }
            if ($item->isDir()) {
                $this->assertDirectory($path, false);
                if (!rmdir($path)) {
                    throw new RuntimeException('phase15_workspace_failed');
                }
            } elseif ('file' === $item->getType()) {
                $this->assertOrdinaryFile($path);
                if (!unlink($path)) {
                    throw new RuntimeException('phase15_workspace_failed');
                }
            } else {
                throw new RuntimeException('phase15_workspace_failed');
            }
        }
        if (!rmdir($this->root)) {
            throw new RuntimeException('phase15_workspace_failed');
        }
    }

    private function assertDirectory(string $path, bool $directChild): void
    {
        $this->assertContained($path);
        if (!is_dir($path) || is_link($path) || false === lstat($path)) {
            throw new RuntimeException('phase15_workspace_failed');
        }
        $resolved = realpath($path);
        if (false === $resolved || 0 !== strcasecmp(self::normalize($resolved), self::normalize($path))) {
            throw new RuntimeException('phase15_workspace_failed');
        }
        if ($directChild && 0 !== strcasecmp(self::normalize(dirname($path)), $this->temporaryRoot)) {
            throw new RuntimeException('phase15_workspace_failed');
        }
    }

    private function assertOrdinaryFile(string $path): void
    {
        $this->assertContained($path);
        if (!is_file($path) || is_link($path) || false === lstat($path)) {
            throw new RuntimeException('phase15_workspace_failed');
        }
        $resolved = realpath($path);
        if (false === $resolved || 0 !== strcasecmp(self::normalize($resolved), self::normalize($path))) {
            throw new RuntimeException('phase15_workspace_failed');
        }
    }

    private function assertContained(string $path): void
    {
        if (!self::isWithin(self::normalize($path), $this->root)) {
            throw new RuntimeException('phase15_workspace_failed');
        }
    }

    private static function isWithin(string $path, string $root): bool
    {
        $path = strtolower(rtrim(self::normalize($path), '/'));
        $root = strtolower(rtrim(self::normalize($root), '/'));

        return $path === $root || str_starts_with($path, $root . '/');
    }

    private static function normalize(string $path): string
    {
        return rtrim(str_replace('\\', '/', $path), '/');
    }
}
