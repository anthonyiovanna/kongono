<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterLocal\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageAdapterLocal\Exception\LocalStorageOperationException;

/** Owns one explicit advisory object-lock lifecycle. */
#[Api(ApiStatus::Internal)]
final class LocalObjectLock
{
    /** @param resource $handle */
    private function __construct(private mixed $handle, private bool $closed = false) {}

    public static function acquire(LocalPathLayout $layout, string $namespace, string $logical, bool $exclusive): self
    {
        $layout->ensureNamespace($namespace);
        $path = $layout->assertContained($layout->lockPath($namespace, $logical), true);
        if (file_exists($path) && (!is_file($path) || is_link($path))) {
            throw LocalStorageOperationException::failed('unexpected_lock_type', 'lock');
        }
        $handle = @fopen($path, 'c+b');
        if (false === $handle) {
            throw LocalStorageOperationException::failed('lock_failed', 'lock');
        }
        $acquired = false;
        try {
            if (!@flock($handle, $exclusive ? LOCK_EX : LOCK_SH)) {
                throw LocalStorageOperationException::failed('lock_failed', 'lock');
            }
            $layout->assertFileHandle($path, $handle);
            $acquired = true;

            return new self($handle);
        } finally {
            if (!$acquired) {
                $unlocked = @flock($handle, LOCK_UN);
                $closed = @fclose($handle);
                if (!$unlocked || !$closed) {
                    throw LocalStorageOperationException::cleanup('lock');
                }
            }
        }
    }

    public function close(): void
    {
        if ($this->closed) {
            return;
        }
        $this->closed = true;
        $unlocked = @flock($this->handle, LOCK_UN);
        $closed = @fclose($this->handle);
        if (!$unlocked || !$closed) {
            throw LocalStorageOperationException::cleanup('lock');
        }
    }

    public function closed(): bool
    {
        return $this->closed;
    }
}
