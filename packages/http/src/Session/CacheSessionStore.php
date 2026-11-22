<?php

declare(strict_types=1);

namespace Kongono\Http\Session;

use Kongono\Core\Cache\CacheInterface;

/** Persists sessions over any Kongono CacheInterface with revocation tombstones preventing concurrent resurrection. */
final readonly class CacheSessionStore implements SessionStoreInterface
{
    public function __construct(
        private CacheInterface $cache,
        private string $prefix = 'session:',
        private int $revocationTtlSeconds = 7200,
    ) {}

    public function read(string $id): ?array
    {
        if ($this->cache->has($this->revokedKey($id))) {
            return null;
        }

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
        // Refuse to resurrect an explicitly destroyed/revoked session ID (e.g. concurrent in-flight request after logout/rotation)
        if ($this->cache->has($this->revokedKey($id))) {
            return;
        }

        $this->cache->set($this->prefix . $id, $data, $ttlSeconds);
    }

    public function destroy(string $id): void
    {
        $this->cache->delete($this->prefix . $id);
        $this->cache->set($this->revokedKey($id), true, max(300, $this->revocationTtlSeconds));
    }

    private function revokedKey(string $id): string
    {
        return $this->prefix . 'revoked:' . $id;
    }
}
