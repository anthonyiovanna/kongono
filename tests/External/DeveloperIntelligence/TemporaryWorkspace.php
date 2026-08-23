<?php

declare(strict_types=1);

namespace Kongono\Tests\External\DeveloperIntelligence;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

/** Owns one verified temporary tree outside the repository and removes only it. */
final class TemporaryWorkspace
{
    private bool $removed = false;

    private function __construct(private readonly string $path, private readonly string $repositoryRoot) {}

    public static function create(string $repositoryRoot): self
    {
        $repository = realpath($repositoryRoot);
        $temporaryRoot = realpath(sys_get_temp_dir());
        if (false === $repository || false === $temporaryRoot || is_link($repository) || is_link($temporaryRoot)) {
            throw new RuntimeException('temporary_root_unavailable');
        }
        $candidate = $temporaryRoot . DIRECTORY_SEPARATOR . 'kongono-phase4h-' . bin2hex(random_bytes(16));
        if (file_exists($candidate) || is_link($candidate) || !mkdir($candidate, 0700)) {
            throw new RuntimeException('temporary_create_failed');
        }
        $resolved = realpath($candidate);
        if (false === $resolved || self::normalize($resolved) !== self::normalize($candidate)) {
            throw new RuntimeException('temporary_resolution_failed');
        }
        $normalized = self::normalize($resolved);
        $normalizedRepository = self::normalize($repository);
        if (self::contains($normalized, $normalizedRepository) || self::contains($normalizedRepository, $normalized)) {
            throw new RuntimeException('temporary_repository_overlap');
        }
        self::assertOrdinaryDirectory($resolved);

        return new self($resolved, $repository);
    }

    public function root(): string
    {
        return $this->path;
    }

    public function directory(string $relative): string
    {
        $path = $this->path($relative);
        if (!is_dir($path) && !mkdir($path, 0700, true)) {
            throw new RuntimeException('temporary_directory_failed');
        }
        $this->assertContainedOrdinary($path, true);

        return $path;
    }

    public function write(string $relative, string $bytes): string
    {
        $path = $this->path($relative);
        $parent = dirname($path);
        if (!is_dir($parent)) {
            $this->directory(substr(str_replace('\\', '/', $parent), strlen(str_replace('\\', '/', $this->path)) + 1));
        }
        if (strlen($bytes) !== file_put_contents($path, $bytes, LOCK_EX)) {
            throw new RuntimeException('temporary_write_failed');
        }
        $this->assertContainedOrdinary($path, false);

        return $path;
    }

    public function path(string $relative): string
    {
        if ('' === $relative || str_contains($relative, '\\') || str_starts_with($relative, '/') || str_contains($relative, "\0")) {
            throw new RuntimeException('temporary_relative_path_invalid');
        }
        $segments = explode('/', $relative);
        if (in_array('', $segments, true) || in_array('.', $segments, true) || in_array('..', $segments, true)) {
            throw new RuntimeException('temporary_relative_path_invalid');
        }

        return $this->path . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $segments);
    }

    public function remove(): void
    {
        if ($this->removed) {
            return;
        }
        $resolved = realpath($this->path);
        if (false === $resolved || self::normalize($resolved) !== self::normalize($this->path)) {
            throw new RuntimeException('temporary_cleanup_identity_failed');
        }
        self::assertOrdinaryDirectory($resolved);
        if (self::contains(self::normalize($resolved), self::normalize($this->repositoryRoot))) {
            throw new RuntimeException('temporary_cleanup_overlap');
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($resolved, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        /** @var SplFileInfo $entry */
        foreach ($iterator as $entry) {
            $entryPath = $entry->getPathname();
            if ($entry->isLink()) {
                throw new RuntimeException('temporary_reparse_detected');
            }
            if ($entry->isDir()) {
                if (!chmod($entryPath, 0700) || !rmdir($entryPath)) {
                    throw new RuntimeException('temporary_cleanup_failed');
                }
            } elseif ($entry->isFile()) {
                if (!chmod($entryPath, 0600) || !unlink($entryPath)) {
                    throw new RuntimeException('temporary_cleanup_failed');
                }
            } else {
                throw new RuntimeException('temporary_special_file_detected');
            }
        }
        if (!chmod($resolved, 0700) || !rmdir($resolved) || file_exists($resolved) || is_link($resolved)) {
            throw new RuntimeException('temporary_cleanup_failed');
        }
        $this->removed = true;
    }

    private function assertContainedOrdinary(string $path, bool $directory): void
    {
        $resolved = realpath($path);
        if (false === $resolved || !self::contains(self::normalize($this->path), self::normalize($resolved)) || is_link($path)) {
            throw new RuntimeException('temporary_containment_failed');
        }
        if ($directory) {
            self::assertOrdinaryDirectory($path);
        } elseif (!is_file($path)) {
            throw new RuntimeException('temporary_special_file_detected');
        }
    }

    private static function assertOrdinaryDirectory(string $path): void
    {
        $stat = @lstat($path);
        if (false === $stat || is_link($path) || !is_dir($path) || (($stat['mode'] & 0170000) !== 0040000)) {
            throw new RuntimeException('temporary_reparse_detected');
        }
    }

    private static function contains(string $root, string $path): bool
    {
        return $root === $path || str_starts_with($path, rtrim($root, '/') . '/');
    }

    private static function normalize(string $path): string
    {
        $path = rtrim(str_replace('\\', '/', $path), '/');

        return '\\' === DIRECTORY_SEPARATOR ? strtolower($path) : $path;
    }
}
