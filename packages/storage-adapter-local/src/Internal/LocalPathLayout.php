<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterLocal\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageAdapterLocal\Configuration\LocalStorageRoot;
use Kongono\StorageAdapterLocal\Exception\LocalStorageOperationException;

/** Derives and proves the closed hashed local layout. */
#[Api(ApiStatus::Internal)]
final readonly class LocalPathLayout
{
    private const string DIRECTORY = '.kongono-storage-local-v1';

    public function __construct(private LocalStorageRoot $root) {}

    public function ensureNamespace(string $namespace): void
    {
        foreach ([$this->base(), $this->namespaces(), $this->locks(), $this->namespaces() . '/' . $this->namespaceHash($namespace), $this->objectDirectory($namespace), $this->lockDirectory($namespace)] as $path) {
            $this->ensureDirectory($path);
        }
    }

    public function objectDirectory(string $namespace): string
    {
        return $this->namespaces() . '/' . $this->namespaceHash($namespace) . '/objects';
    }

    public function objectPath(string $namespace, string $logical): string
    {
        return $this->objectDirectory($namespace) . '/' . $this->objectHash($namespace, $logical) . '.record';
    }

    public function temporaryPath(string $namespace, string $logical, string $operation): string
    {
        return $this->objectDirectory($namespace) . '/.' . $this->objectHash($namespace, $logical) . '.' . hash('sha256', "kongono.storage.local.temp.v1\0" . $operation) . '.tmp';
    }

    public function lockPath(string $namespace, string $logical): string
    {
        return $this->lockDirectory($namespace) . '/' . $this->objectHash($namespace, $logical) . '.lock';
    }

    public function namespaceHash(string $namespace): string
    {
        return hash('sha256', "kongono.storage.local.namespace.v1\0" . $namespace);
    }

    public function objectHash(string $namespace, string $logical): string
    {
        return hash('sha256', "kongono.storage.local.object.v1\0" . $namespace . "\0" . $logical);
    }

    public function assertContained(string $path, bool $mayBeMissing = false, bool $directory = false): string
    {
        return LocalAdapterGuard::contained($this->root->resolvedPath(), $path, $mayBeMissing, $directory);
    }

    /** @param resource $handle */
    public function assertFileHandle(string $path, mixed $handle): void
    {
        $this->assertContained($path);
        $opened = fstat($handle);
        $current = @lstat($path);
        if (false === $opened || false === $current || 0100000 !== ($opened['mode'] & 0170000) || $opened['dev'] !== $current['dev'] || $opened['ino'] !== $current['ino']) {
            throw LocalStorageOperationException::failed('unexpected_path_type', 'prepare');
        }
    }

    private function base(): string
    {
        return $this->root->resolvedPath() . '/' . self::DIRECTORY;
    }
    private function namespaces(): string
    {
        return $this->base() . '/namespaces';
    }
    private function locks(): string
    {
        return $this->base() . '/locks';
    }
    private function lockDirectory(string $namespace): string
    {
        return $this->locks() . '/' . $this->namespaceHash($namespace);
    }

    private function ensureDirectory(string $path): void
    {
        $this->assertContained($path, true, true);
        if (!is_dir($path) && !@mkdir($path, 0700) && !is_dir($path)) {
            throw LocalStorageOperationException::failed('directory_creation_failed', 'prepare');
        }
        if (is_link($path)) {
            throw LocalStorageOperationException::failed('unexpected_path_type', 'prepare');
        }
        $this->assertContained($path, false, true);
    }
}
