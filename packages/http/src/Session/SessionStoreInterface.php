<?php

declare(strict_types=1);

namespace Kongono\Http\Session;

interface SessionStoreInterface
{
    /** @return array<string, mixed>|null */
    public function read(string $id): ?array;

    /** @param array<string, mixed> $data */
    public function write(string $id, array $data, int $ttlSeconds): void;

    public function destroy(string $id): void;
}
