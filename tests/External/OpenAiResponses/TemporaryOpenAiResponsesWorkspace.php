<?php

declare(strict_types=1);

namespace Kongono\Tests\External\OpenAiResponses;

use RuntimeException;

final class TemporaryOpenAiResponsesWorkspace
{
    private string $root;
    private string $temporaryRoot;

    public function __construct(string $repositoryRoot)
    {
        $temporary = realpath(sys_get_temp_dir());
        $repository = realpath($repositoryRoot);
        if (false === $temporary || false === $repository) {
            throw new RuntimeException('phase17_workspace_failed');
        }
        $this->temporaryRoot = self::normalize($temporary);
        $repository = self::normalize($repository);
        $this->root = $this->temporaryRoot . '/kongono-phase17-' . bin2hex(random_bytes(16));
        if (self::isWithin($this->root, $repository) || !mkdir($this->root, 0700)) {
            throw new RuntimeException('phase17_workspace_failed');
        }
        $this->assertDirectory($this->root, true);
    }

    public function root(): string
    {
        return $this->root;
    }

    public function directory(string $name): string
    {
        $this->assertName($name);
        $path = $this->root . '/' . $name;
        if (!mkdir($path, 0700)) {
            throw new RuntimeException('phase17_workspace_failed');
        }
        $this->assertDirectory($path, false);

        return $path;
    }

    public function write(string $name, string $bytes): string
    {
        $this->assertName($name);
        $path = $this->root . '/' . $name;
        if (strlen($bytes) !== file_put_contents($path, $bytes, LOCK_EX)) {
            throw new RuntimeException('phase17_workspace_failed');
        }
        @chmod($path, 0600);
        $this->assertOrdinaryFile($path);

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
                throw new RuntimeException('phase17_workspace_failed');
            }
            $path = self::normalize($item->getPathname());
            $this->assertContained($path);
            if ($item->isLink()) {
                throw new RuntimeException('phase17_workspace_failed');
            }
            if ($item->isDir()) {
                $this->assertDirectory($path, false);
                if (!rmdir($path)) {
                    throw new RuntimeException('phase17_workspace_failed');
                }
            } elseif ('file' === $item->getType()) {
                $this->assertOrdinaryFile($path);
                if (!unlink($path)) {
                    throw new RuntimeException('phase17_workspace_failed');
                }
            } else {
                throw new RuntimeException('phase17_workspace_failed');
            }
        }
        if (!rmdir($this->root)) {
            throw new RuntimeException('phase17_workspace_failed');
        }
    }

    public static function assertOwnedFile(string $path): void
    {
        $temporary = realpath(sys_get_temp_dir());
        $resolved = realpath($path);
        if (false === $temporary || false === $resolved || !is_file($path) || is_link($path) || self::isReparsePoint($path) || false === lstat($path)) {
            throw new RuntimeException('phase17_workspace_failed');
        }
        $temporary = self::normalize($temporary);
        $resolved = self::normalize($resolved);
        $parent = self::normalize(dirname($resolved));
        if (0 !== strcasecmp($resolved, self::normalize($path))
            || 0 !== strcasecmp(self::normalize(dirname($parent)), $temporary)
            || 1 !== preg_match('/\Akongono-phase17-[a-f0-9]{32}\z/D', basename($parent))
        ) {
            throw new RuntimeException('phase17_workspace_failed');
        }
    }

    private function assertName(string $name): void
    {
        if ('' === $name || str_contains($name, "\0") || str_contains($name, '/') || str_contains($name, '\\') || '.' === $name || '..' === $name) {
            throw new RuntimeException('phase17_workspace_failed');
        }
    }

    private function assertDirectory(string $path, bool $directChild): void
    {
        $this->assertContained($path);
        if (!is_dir($path) || is_link($path) || self::isReparsePoint($path) || false === lstat($path)) {
            throw new RuntimeException('phase17_workspace_failed');
        }
        $resolved = realpath($path);
        if (false === $resolved || 0 !== strcasecmp(self::normalize($resolved), self::normalize($path))) {
            throw new RuntimeException('phase17_workspace_failed');
        }
        if ($directChild && 0 !== strcasecmp(self::normalize(dirname($path)), $this->temporaryRoot)) {
            throw new RuntimeException('phase17_workspace_failed');
        }
    }

    private function assertOrdinaryFile(string $path): void
    {
        $this->assertContained($path);
        if (!is_file($path) || is_link($path) || self::isReparsePoint($path) || false === lstat($path)) {
            throw new RuntimeException('phase17_workspace_failed');
        }
        $resolved = realpath($path);
        if (false === $resolved || 0 !== strcasecmp(self::normalize($resolved), self::normalize($path))) {
            throw new RuntimeException('phase17_workspace_failed');
        }
    }

    private function assertContained(string $path): void
    {
        if (!self::isWithin(self::normalize($path), $this->root)) {
            throw new RuntimeException('phase17_workspace_failed');
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
