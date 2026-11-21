<?php

declare(strict_types=1);

namespace Kongono\Storage;

/** Deterministic in-memory storage adapter with full optimistic-concurrency semantics. */
final class MemoryStorage implements StorageInterface
{
    /** @var array<string, array{bytes: string, meta: ObjectMeta}> */
    private array $objects = [];

    public function put(string $key, string $contents, string $contentType = 'application/octet-stream', array $metadata = []): WriteResult
    {
        $key = $this->normalizeKey($key);
        $existed = isset($this->objects[$key]);
        $meta = $this->buildMeta($key, $contents, $contentType, $metadata);
        $this->objects[$key] = ['bytes' => $contents, 'meta' => $meta];

        return new WriteResult($existed ? StorageOutcome::Replaced : StorageOutcome::Created, $meta);
    }

    public function putIfAbsent(string $key, string $contents, string $contentType = 'application/octet-stream', array $metadata = []): WriteResult
    {
        $key = $this->normalizeKey($key);
        if (isset($this->objects[$key])) {
            return new WriteResult(StorageOutcome::AlreadyExists, $this->objects[$key]['meta']);
        }

        return $this->put($key, $contents, $contentType, $metadata);
    }

    public function replaceIfVersion(string $key, string $expectedVersion, string $contents, string $contentType = 'application/octet-stream', array $metadata = []): WriteResult
    {
        $key = $this->normalizeKey($key);
        if (!isset($this->objects[$key])) {
            return new WriteResult(StorageOutcome::Absent);
        }
        if (!hash_equals($this->objects[$key]['meta']->version, $expectedVersion)) {
            return new WriteResult(StorageOutcome::Conflict, $this->objects[$key]['meta']);
        }

        return $this->put($key, $contents, $contentType, $metadata);
    }

    public function get(string $key): ?string
    {
        $key = $this->normalizeKey($key);

        return $this->objects[$key]['bytes'] ?? null;
    }

    public function meta(string $key): ?ObjectMeta
    {
        $key = $this->normalizeKey($key);

        return $this->objects[$key]['meta'] ?? null;
    }

    public function exists(string $key): bool
    {
        return isset($this->objects[$this->normalizeKey($key)]);
    }

    public function delete(string $key, ?string $expectedVersion = null): StorageOutcome
    {
        $key = $this->normalizeKey($key);
        if (!isset($this->objects[$key])) {
            return StorageOutcome::Absent;
        }
        if (null !== $expectedVersion && !hash_equals($this->objects[$key]['meta']->version, $expectedVersion)) {
            return StorageOutcome::Conflict;
        }
        unset($this->objects[$key]);

        return StorageOutcome::Deleted;
    }

    public function list(string $prefix = ''): array
    {
        $results = [];
        foreach ($this->objects as $key => $entry) {
            if ('' === $prefix || str_starts_with($key, $prefix)) {
                $results[] = $entry['meta'];
            }
        }
        usort($results, static fn(ObjectMeta $a, ObjectMeta $b): int => strcmp($a->key, $b->key));

        return $results;
    }

    private function normalizeKey(string $key): string
    {
        $clean = ltrim(str_replace('\\', '/', trim($key)), '/');
        if ('' === $clean || str_contains($clean, '..') || str_contains($clean, "\0")) {
            throw new StorageException(sprintf('Invalid storage key "%s".', $key));
        }

        return $clean;
    }

    /** @param array<string, string> $metadata */
    private function buildMeta(string $key, string $contents, string $contentType, array $metadata): ObjectMeta
    {
        return new ObjectMeta(
            key: $key,
            size: strlen($contents),
            sha256: hash('sha256', $contents),
            version: bin2hex(random_bytes(16)),
            contentType: $contentType,
            updatedAt: gmdate(DATE_ATOM),
            metadata: $metadata,
        );
    }
}
