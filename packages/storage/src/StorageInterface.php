<?php

declare(strict_types=1);

namespace Kongono\Storage;

interface StorageInterface
{
    /** @param array<string, string> $metadata */
    public function put(string $key, string $contents, string $contentType = 'application/octet-stream', array $metadata = []): WriteResult;

    /** @param array<string, string> $metadata */
    public function putIfAbsent(string $key, string $contents, string $contentType = 'application/octet-stream', array $metadata = []): WriteResult;

    /** @param array<string, string> $metadata */
    public function replaceIfVersion(string $key, string $expectedVersion, string $contents, string $contentType = 'application/octet-stream', array $metadata = []): WriteResult;

    public function get(string $key): ?string;

    public function meta(string $key): ?ObjectMeta;

    public function exists(string $key): bool;

    public function delete(string $key, ?string $expectedVersion = null): StorageOutcome;

    /** @return list<ObjectMeta> */
    public function list(string $prefix = ''): array;
}
