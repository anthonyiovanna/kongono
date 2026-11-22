<?php

declare(strict_types=1);

namespace Kongono\Storage;

/**
 * Explicit, persistent-runtime-safe named storage disk registry (zero global facades).
 *
 * Register named disks (`public`, `private`, `s3`, `backups`, etc.) in `app/bootstrap.php`
 * and inject `StorageDisks` into controllers or services via the DI container.
 */
final class StorageDisks
{
    /** @var array<string, StorageInterface> */
    private array $disks = [];

    private string $defaultDisk;

    /** @param array<string, StorageInterface> $disks */
    public function __construct(array $disks = [], string $default = 'default')
    {
        $this->defaultDisk = $this->normalizeName($default);
        foreach ($disks as $name => $storage) {
            $this->disks[$this->normalizeName($name)] = $storage;
        }
        if ([] !== $this->disks && !isset($this->disks[$this->defaultDisk])) {
            $this->defaultDisk = array_key_first($this->disks);
        }
    }

    public function register(string $name, StorageInterface $storage, bool $asDefault = false): self
    {
        $clean = $this->normalizeName($name);
        $this->disks[$clean] = $storage;
        if ($asDefault || 1 === count($this->disks) && !isset($this->disks[$this->defaultDisk])) {
            $this->defaultDisk = $clean;
        }

        return $this;
    }

    public function disk(?string $name = null): StorageInterface
    {
        $target = null !== $name && '' !== trim($name) ? $this->normalizeName($name) : $this->defaultDisk;
        if (!isset($this->disks[$target])) {
            throw new StorageException(sprintf('Storage disk "%s" is not registered.', $target));
        }

        return $this->disks[$target];
    }

    public function has(string $name): bool
    {
        return isset($this->disks[$this->normalizeName($name)]);
    }

    public function defaultName(): string
    {
        return $this->defaultDisk;
    }

    /** @return list<string> */
    public function names(): array
    {
        $names = array_keys($this->disks);
        sort($names, SORT_STRING);

        return $names;
    }

    private function normalizeName(string $name): string
    {
        $clean = strtolower(trim($name));
        if ('' === $clean || 1 !== preg_match('/\A[a-z0-9][a-z0-9_-]{0,63}\z/', $clean)) {
            throw new StorageException(sprintf('Invalid storage disk name "%s".', $name));
        }

        return $clean;
    }
}
