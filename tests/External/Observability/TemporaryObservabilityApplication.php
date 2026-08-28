<?php

declare(strict_types=1);

namespace Kongono\Tests\External\Observability;

use FilesystemIterator;
use JsonException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

/** Owns the copied observability skeleton, package mirror, and all acceptance residue. */
final class TemporaryObservabilityApplication
{
    private const string PREFIX = 'kongono phase10h-';

    private function __construct(
        private readonly string $root,
        private readonly string $repositoryRoot,
        private readonly string $applicationRoot,
    ) {}

    public static function create(string $repositoryRoot): self
    {
        $repository = realpath($repositoryRoot);
        $temporary = realpath(sys_get_temp_dir());
        if (false === $repository || false === $temporary || self::linkLike($repository) || self::linkLike($temporary)) {
            throw new RuntimeException('temporary_authority_unavailable');
        }

        $repository = self::normalize($repository);
        $temporary = rtrim(self::normalize($temporary), '/');
        $root = $temporary . '/' . self::PREFIX . bin2hex(random_bytes(16));
        if (file_exists($root) || is_link($root) || !mkdir($root, 0700)) {
            throw new RuntimeException('temporary_create_failed');
        }
        $resolved = realpath($root);
        if (false === $resolved || self::normalize($resolved) !== $root || !str_contains($root, ' ')) {
            throw new RuntimeException('temporary_identity_invalid');
        }
        if (str_starts_with(strtolower($repository . '/'), strtolower($root . '/')) || str_starts_with(strtolower($root . '/'), strtolower($repository . '/'))) {
            throw new RuntimeException('temporary_repository_overlap');
        }

        $application = new self($root, $repository, $root . '/skeletons/observability-minimal');
        $application->populate();

        return $application;
    }

    public function root(): string
    {
        return $this->root;
    }

    public function applicationRoot(): string
    {
        return $this->applicationRoot;
    }

    public function buildDirectory(): string
    {
        return $this->applicationRoot . '/var/kongono/build';
    }

    public function source(): string
    {
        return $this->applicationRoot . '/kongono.observability.json';
    }

    public function composerLock(): string
    {
        return $this->applicationRoot . '/composer.lock';
    }

    public function read(string $path): string
    {
        $this->requireContained($path);

        return self::readOrdinary($path);
    }

    public function write(string $path, string $bytes): void
    {
        $this->requireContained($path);
        $parent = dirname($path);
        if (!is_dir($parent) && !mkdir($parent, 0700, true)) {
            throw new RuntimeException('temporary_parent_create_failed');
        }
        if (self::linkLike($parent)) {
            throw new RuntimeException('temporary_parent_link');
        }
        if (strlen($bytes) !== file_put_contents($path, $bytes, LOCK_EX)) {
            throw new RuntimeException('temporary_write_failed');
        }
    }

    public function unlink(string $path): void
    {
        $this->requireContained($path);
        self::readOrdinary($path);
        if (!unlink($path)) {
            throw new RuntimeException('temporary_unlink_failed');
        }
    }

    /** @return list<string> */
    public function buildFiles(): array
    {
        $entries = scandir($this->buildDirectory());
        if (false === $entries) {
            throw new RuntimeException('build_scan_failed');
        }
        $files = [];
        foreach ($entries as $entry) {
            if ('.' === $entry || '..' === $entry || '.gitignore' === $entry) {
                continue;
            }
            self::readOrdinary($this->buildDirectory() . '/' . $entry);
            $files[] = $entry;
        }
        sort($files, SORT_STRING);

        return $files;
    }

    public function treeIdentity(): string
    {
        return self::directoryIdentity($this->root);
    }

    public function buildIdentity(): string
    {
        return self::directoryIdentity($this->buildDirectory());
    }

    public function cleanup(): void
    {
        $resolved = realpath($this->root);
        $temporary = realpath(sys_get_temp_dir());
        if (false === $resolved || false === $temporary) {
            throw new RuntimeException('temporary_cleanup_identity_failed');
        }
        $resolved = self::normalize($resolved);
        $temporary = rtrim(self::normalize($temporary), '/');
        if (dirname($resolved) !== $temporary || !str_starts_with(basename($resolved), self::PREFIX)) {
            throw new RuntimeException('temporary_cleanup_scope_invalid');
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($resolved, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        /** @var SplFileInfo $entry */
        foreach ($iterator as $entry) {
            $path = self::normalize($entry->getPathname());
            if (self::linkLike($path)) {
                throw new RuntimeException('temporary_cleanup_link');
            }
            if ($entry->isDir()) {
                @chmod($path, 0700);
                self::removeWithRetry($path, true);
            } elseif ($entry->isFile()) {
                @chmod($path, 0600);
                self::removeWithRetry($path, false);
            } else {
                throw new RuntimeException('temporary_cleanup_special');
            }
        }
        @chmod($resolved, 0700);
        self::removeWithRetry($resolved, true);
    }

    private function populate(): void
    {
        $source = $this->repositoryRoot . '/skeletons/observability-minimal';
        self::copyTree($source, $this->applicationRoot);
        if (self::directoryIdentity($source) !== self::directoryIdentity($this->applicationRoot)) {
            throw new RuntimeException('skeleton_copy_identity_mismatch');
        }

        $lock = self::decodeObject(self::readOrdinary($this->composerLock()));
        $packages = $lock['packages'] ?? null;
        if (!is_array($packages) || [] === $packages || !array_is_list($packages)) {
            throw new RuntimeException('package_mirror_manifest_invalid');
        }
        foreach ($packages as $index => $package) {
            if (!is_array($package) || !is_string($package['name'] ?? null)) {
                throw new RuntimeException('package_mirror_record_invalid');
            }
            $name = $package['name'];
            if (str_starts_with($name, 'kongono/')) {
                $component = substr($name, strlen('kongono/'));
                if ('' === $component || basename($component) !== $component) {
                    throw new RuntimeException('package_mirror_name_invalid');
                }
                self::copyTree($this->repositoryRoot . '/packages/' . $component, $this->root . '/packages/' . $component);
                continue;
            }

            $parts = explode('/', $name);
            if (2 !== count($parts) || '' === $parts[0] || '' === $parts[1]) {
                throw new RuntimeException('package_mirror_name_invalid');
            }
            $mirror = $parts[0] . '-' . $parts[1];
            self::copyTree($this->repositoryRoot . '/vendor/' . $parts[0] . '/' . $parts[1], $this->root . '/packages/' . $mirror);
            $dist = $package['dist'] ?? null;
            if (!is_array($dist) || !is_string($dist['reference'] ?? null)) {
                throw new RuntimeException('package_mirror_dist_invalid');
            }
            $dist['type'] = 'path';
            $dist['url'] = '../../packages/' . $mirror;
            $package['dist'] = $dist;
            $package['transport-options'] = ['symlink' => false, 'relative' => true];
            $packages[$index] = $package;
        }
        $lock['packages'] = $packages;
        $this->write($this->composerLock(), json_encode($lock, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n");
    }

    private function requireContained(string $path): void
    {
        if (!str_starts_with(self::normalize($path), $this->root . '/')) {
            throw new RuntimeException('temporary_path_escape');
        }
    }

    private static function copyTree(string $source, string $destination): void
    {
        $resolvedSource = realpath($source);
        if (false === $resolvedSource || self::linkLike($source) || !is_dir($resolvedSource)) {
            throw new RuntimeException('copy_source_invalid');
        }
        $resolvedSource = rtrim(self::normalize($resolvedSource), '/');
        if (!is_dir($destination) && !mkdir($destination, 0700, true)) {
            throw new RuntimeException('copy_destination_create_failed');
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($resolvedSource, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );
        /** @var SplFileInfo $entry */
        foreach ($iterator as $entry) {
            $sourcePath = self::normalize($entry->getPathname());
            if (self::linkLike($sourcePath)) {
                throw new RuntimeException('copy_link_rejected');
            }
            $relative = substr($sourcePath, strlen($resolvedSource) + 1);
            $target = self::normalize($destination) . '/' . $relative;
            if ($entry->isDir()) {
                if (!is_dir($target) && !mkdir($target, 0700, true)) {
                    throw new RuntimeException('copy_directory_failed');
                }
                continue;
            }
            if (!$entry->isFile()) {
                throw new RuntimeException('copy_special_rejected');
            }
            $bytes = self::readOrdinary($sourcePath);
            if (strlen($bytes) !== file_put_contents($target, $bytes, LOCK_EX)) {
                throw new RuntimeException('copy_file_failed');
            }
        }
    }

    private static function directoryIdentity(string $root): string
    {
        $resolved = realpath($root);
        if (false === $resolved || self::linkLike($root) || !is_dir($resolved)) {
            throw new RuntimeException('identity_root_invalid');
        }
        $resolved = rtrim(self::normalize($resolved), '/');
        $records = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($resolved, FilesystemIterator::SKIP_DOTS));
        /** @var SplFileInfo $entry */
        foreach ($iterator as $entry) {
            $path = self::normalize($entry->getPathname());
            if (self::linkLike($path)) {
                throw new RuntimeException('identity_link_rejected');
            }
            if (!$entry->isFile()) {
                continue;
            }
            $bytes = self::readOrdinary($path);
            $records[] = substr($path, strlen($resolved) + 1) . "\0" . strlen($bytes) . "\0" . hash('sha256', $bytes);
        }
        sort($records, SORT_STRING);

        return hash('sha256', implode("\n", $records));
    }

    /** @return array<string, mixed> */
    private static function decodeObject(string $bytes): array
    {
        try {
            $value = json_decode($bytes, true, 128, JSON_THROW_ON_ERROR);
        } catch (JsonException $failure) {
            throw new RuntimeException('json_invalid', 0, $failure);
        }
        if (!is_array($value) || array_is_list($value)) {
            throw new RuntimeException('json_object_required');
        }

        $object = [];
        foreach ($value as $key => $item) {
            if (!is_string($key)) {
                throw new RuntimeException('json_object_required');
            }
            $object[$key] = $item;
        }

        return $object;
    }

    private static function readOrdinary(string $path): string
    {
        $stat = @lstat($path);
        if (false === $stat || self::linkLike($path) || !is_file($path) || 0100000 !== ($stat['mode'] & 0170000)) {
            throw new RuntimeException('ordinary_file_required');
        }
        $bytes = file_get_contents($path);
        if (false === $bytes || strlen($bytes) !== $stat['size']) {
            throw new RuntimeException('ordinary_file_read_failed');
        }

        return $bytes;
    }

    private static function linkLike(string $path): bool
    {
        if (is_link($path)) {
            return true;
        }
        $target = @readlink($path);

        return false !== $target && strtolower(self::normalize($target)) !== strtolower(self::normalize($path));
    }

    private static function removeWithRetry(string $path, bool $directory): void
    {
        for ($attempt = 0; $attempt < 100; ++$attempt) {
            if (($directory ? @rmdir($path) : @unlink($path)) || !file_exists($path) && !is_link($path)) {
                return;
            }
            usleep(20_000);
        }
        throw new RuntimeException('temporary_cleanup_failed');
    }

    private static function normalize(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}
