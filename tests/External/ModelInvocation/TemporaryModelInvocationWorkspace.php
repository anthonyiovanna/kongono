<?php

declare(strict_types=1);

namespace Kongono\Tests\External\ModelInvocation;

use RuntimeException;

final class TemporaryModelInvocationWorkspace
{
    private string $root;

    public function __construct(string $repositoryRoot)
    {
        $base = str_replace('\\', '/', sys_get_temp_dir());
        $resolvedRepository = realpath($repositoryRoot);
        $repository = strtolower(rtrim(str_replace('\\', '/', false === $resolvedRepository ? $repositoryRoot : $resolvedRepository), '/'));
        $this->root = $base . '/kongono-phase14-' . bin2hex(random_bytes(16));
        if (str_starts_with(strtolower($this->root), $repository . '/') || !mkdir($this->root, 0700)) {
            throw new RuntimeException('phase14_workspace_failed');
        }
        $this->assertContained($this->root);
    }

    public function root(): string
    {
        return $this->root;
    }

    public function directory(string $name): string
    {
        if ('' === $name || str_contains($name, "\0") || str_contains($name, '/') || str_contains($name, '\\') || '.' === $name || '..' === $name) {
            throw new RuntimeException('phase14_workspace_failed');
        }
        $path = $this->root . '/' . $name;
        if (!mkdir($path, 0700)) {
            throw new RuntimeException('phase14_workspace_failed');
        }
        $this->assertContained($path);

        return $path;
    }

    public function cleanup(): void
    {
        if (!is_dir($this->root)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($items as $item) {
            if (!$item instanceof \SplFileInfo) {
                throw new RuntimeException('phase14_workspace_failed');
            }
            $path = str_replace('\\', '/', $item->getPathname());
            $this->assertContained($path);
            if ($item->isLink() || ($item->getFileInfo()->getType() !== 'file' && !$item->isDir())) {
                throw new RuntimeException('phase14_workspace_failed');
            }
            $item->isDir() ? rmdir($path) : unlink($path);
        }
        rmdir($this->root);
    }

    private function assertContained(string $path): void
    {
        $normalized = str_replace('\\', '/', $path);
        if ($normalized !== $this->root && !str_starts_with($normalized, $this->root . '/')) {
            throw new RuntimeException('phase14_workspace_failed');
        }
        if (is_link($path) || (function_exists('lstat') && false === lstat($path))) {
            throw new RuntimeException('phase14_workspace_failed');
        }
        if (PHP_OS_FAMILY === 'Windows' && is_dir($path)) {
            $attributes = @fileperms($path);
            if (false === $attributes) {
                throw new RuntimeException('phase14_workspace_failed');
            }
        }
    }
}
