<?php

declare(strict_types=1);

namespace Kongono\Storage;

interface StorageInterface
{
    /** @param array<string, string> $metadata */
    public function put(string $key, string $contents, string $contentType = 'application/octet-stream', array $metadata = []): WriteResult;

    /**
     * Stream bytes from an open readable PHP stream resource into storage in constant memory.
     *
     * @param resource              $stream
     * @param array<string, string> $metadata
     */
    public function putStream(string $key, mixed $stream, string $contentType = 'application/octet-stream', array $metadata = []): WriteResult;

    /** @param array<string, string> $metadata */
    public function putIfAbsent(string $key, string $contents, string $contentType = 'application/octet-stream', array $metadata = []): WriteResult;

    /** @param array<string, string> $metadata */
    public function replaceIfVersion(string $key, string $expectedVersion, string $contents, string $contentType = 'application/octet-stream', array $metadata = []): WriteResult;

    public function get(string $key): ?string;

    /**
     * Open a readable PHP stream resource for an existing object (or null if absent).
     * The caller is responsible for closing the returned handle with `fclose()`.
     *
     * @return resource|null
     */
    public function getStream(string $key): mixed;

    public function meta(string $key): ?ObjectMeta;

    public function exists(string $key): bool;

    /** Copy an object to a new key (uses native server-side copy on S3). */
    public function copy(string $fromKey, string $toKey): WriteResult;

    /** Move/rename an object to a new key. */
    public function move(string $fromKey, string $toKey): WriteResult;

    public function delete(string $key, ?string $expectedVersion = null): StorageOutcome;

    /** @return list<ObjectMeta> */
    public function list(string $prefix = ''): array;
}
