<?php

declare(strict_types=1);

namespace Kongono\Http\Session;

use Kongono\Core\Cache\CacheInterface;

/** Persists sessions over any Kongono CacheInterface (MemoryCache, FileCache, etc.). */
final readonly class CacheSessionStore implements SessionStoreInterface
{
    public function __construct(
        private CacheInterface $cache,
        private string $prefix = 'session:',
    ) {}

    public function read(string $id): ?array
    {
        $raw = $this->cache->get($this->prefix . $id);
        if (!is_array($raw)) {
            return null;
        }

        /** @var array<string, mixed> $typed */
        $typed = [];
        foreach ($raw as $k => $v) {
            $typed[(string) $k] = $v;
        }

        return $typed;
    }

    public function write(string $id, array $data, int $ttlSeconds): void
    {
        $this->cache->set($this->prefix . $id, $data, $ttlSeconds);
    }

    public function destroy(string $id): void
    {
        $this->cache->delete($this->prefix . $id);
    }
}
