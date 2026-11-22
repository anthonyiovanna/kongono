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

    public function putStream(string $key, mixed $stream, string $contentType = 'application/octet-stream', array $metadata = []): WriteResult
    {
        if (!is_resource($stream)) {
            throw new StorageException('putStream() requires an open PHP stream resource.');
        }

        $buf = '';
        while (!feof($stream)) {
            $chunk = fread($stream, 65536);
            if (false === $chunk) {
                throw new StorageException('Failed to read from input stream.');
            }
            if ('' === $chunk) {
                break;
            }
            $buf .= $chunk;
        }

        return $this->put($key, $buf, $contentType, $metadata);
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

    public function getStream(string $key): mixed
    {
        $bytes = $this->get($key);
        if (null === $bytes) {
            return null;
        }

        $handle = fopen('php://temp', 'r+b');
        if (false === $handle) {
            throw new StorageException('Unable to open temporary memory stream.');
        }
        fwrite($handle, $bytes);
        rewind($handle);

        return $handle;
    }

    public function copy(string $fromKey, string $toKey): WriteResult
    {
        $cleanFrom = $this->normalizeKey($fromKey);
        $cleanTo = $this->normalizeKey($toKey);
        if (!isset($this->objects[$cleanFrom])) {
            return new WriteResult(StorageOutcome::Absent);
        }
        if ($cleanFrom === $cleanTo) {
            return new WriteResult(StorageOutcome::Replaced, $this->objects[$cleanFrom]['meta']);
        }

        $entry = $this->objects[$cleanFrom];

        return $this->put($cleanTo, $entry['bytes'], $entry['meta']->contentType, $entry['meta']->metadata);
    }

    public function move(string $fromKey, string $toKey): WriteResult
    {
        $cleanFrom = $this->normalizeKey($fromKey);
        $cleanTo = $this->normalizeKey($toKey);
        if ($cleanFrom === $cleanTo) {
            return isset($this->objects[$cleanFrom])
                ? new WriteResult(StorageOutcome::Replaced, $this->objects[$cleanFrom]['meta'])
                : new WriteResult(StorageOutcome::Absent);
        }

        $copied = $this->copy($cleanFrom, $cleanTo);
        if (!$copied->succeeded()) {
            return $copied;
        }
        unset($this->objects[$cleanFrom]);

        return $copied;
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
        if (1 === preg_match('/[\x00-\x1f\x7f]/', $key)) {
            throw new StorageException(sprintf('Invalid storage key "%s".', $key));
        }
        $clean = ltrim(str_replace('\\', '/', trim($key)), '/');
        if ('' === $clean) {
            throw new StorageException(sprintf('Invalid storage key "%s".', $key));
        }
        foreach (explode('/', $clean) as $segment) {
            if ('' === $segment || '.' === $segment || '..' === $segment) {
                throw new StorageException(sprintf('Invalid storage key "%s".', $key));
            }
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
