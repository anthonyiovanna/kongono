<?php

declare(strict_types=1);

namespace Kongono\Tests\External\ApplicationEvents;

use RuntimeException;

/** Keeps one no-clobber private consumer and all raw evidence for review. */
final class TemporaryEventWorkspace
{
    private readonly string $root;

    public function __construct(string $repositoryRoot, string $requestedRoot)
    {
        $temporary = realpath(sys_get_temp_dir());
        $repository = realpath($repositoryRoot);
        if (false === $temporary || false === $repository || !is_dir($temporary) || is_link($temporary)) {
            throw new RuntimeException('phase22_workspace_failed');
        }
        $temporary = self::normalize($temporary);
        $repository = self::normalize($repository);
        $requestedRoot = self::normalize($requestedRoot);
        if (1 !== preg_match('~/kongono-phase22f-[0-9a-f]{32}\z~D', $requestedRoot)
            || !self::within($requestedRoot, $temporary)
            || self::within($requestedRoot, $repository)
            || file_exists($requestedRoot)
            || is_link($requestedRoot)
            || self::reparse($requestedRoot)
        ) {
            throw new RuntimeException('phase22_workspace_failed');
        }
        $parent = dirname($requestedRoot);
        if (false === realpath($parent) || 0 !== strcasecmp(self::normalize((string) realpath($parent)), $temporary)) {
            throw new RuntimeException('phase22_workspace_failed');
        }
        if (!mkdir($requestedRoot, 0700)) {
            throw new RuntimeException('phase22_workspace_failed');
        }
        $this->root = $requestedRoot;
        $this->assertDirectory($this->root);
        $this->directory('capture');
        $this->directory('evidence');
        $this->directory('work');
        $this->directory('composer-cache');
        $this->directory('composer-home');
    }

    public function root(): string
    {
        return $this->root;
    }

    public function path(string $relative): string
    {
        $relative = str_replace('\\', '/', $relative);
        if ('' === $relative || str_starts_with($relative, '/') || str_contains($relative, "\0")) {
            throw new RuntimeException('phase22_workspace_failed');
        }
        foreach (explode('/', $relative) as $segment) {
            if (1 !== preg_match('/\A[A-Za-z0-9._-]+\z/D', $segment) || '.' === $segment || '..' === $segment) {
                throw new RuntimeException('phase22_workspace_failed');
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
            if (!file_exists($cursor) && !mkdir($cursor, 0700)) {
                throw new RuntimeException('phase22_workspace_failed');
            }
            $this->assertDirectory($cursor);
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
        if (file_exists($path) || is_link($path) || self::reparse($path)) {
            throw new RuntimeException('phase22_workspace_failed');
        }
        $handle = fopen($path, 'xb');
        if (false === $handle) {
            throw new RuntimeException('phase22_workspace_failed');
        }
        try {
            if (strlen($bytes) !== fwrite($handle, $bytes)) {
                throw new RuntimeException('phase22_workspace_failed');
            }
        } finally {
            fclose($handle);
        }
        $this->assertFile($path);

        return $path;
    }

    public function emptyFile(string $relative): string
    {
        return $this->write($relative, '');
    }

    public function assertFile(string $path): void
    {
        if (!self::within(self::normalize($path), $this->root)
            || !is_file($path)
            || is_link($path)
            || self::reparse($path)
            || false === lstat($path)
            || 0 !== strcasecmp(self::normalize((string) realpath($path)), self::normalize($path))
        ) {
            throw new RuntimeException('phase22_workspace_failed');
        }
    }

    public function assertDirectory(string $path): void
    {
        if (!self::within(self::normalize($path), $this->root)
            || !is_dir($path)
            || is_link($path)
            || self::reparse($path)
            || false === lstat($path)
            || 0 !== strcasecmp(self::normalize((string) realpath($path)), self::normalize($path))
        ) {
            throw new RuntimeException('phase22_workspace_failed');
        }
    }

    public static function ordinary(string $path): bool
    {
        return is_file($path) && !is_link($path) && !self::reparse($path) && false !== lstat($path)
            && 0 === strcasecmp(self::normalize((string) realpath($path)), self::normalize($path));
    }

    public static function within(string $path, string $root): bool
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
