<?php

declare(strict_types=1);

namespace Kongono\Jobs\Workflow;

use Kongono\Core\Cache\CacheInterface;

/** Cache-backed durable workflow store (works with RedisCache, FileCache, or MemoryCache). */
final readonly class CacheWorkflowStore implements WorkflowStoreInterface
{
    public function __construct(
        private CacheInterface $cache,
        private string $prefix = 'kongono:workflow:',
        private int $ttlSeconds = 604800,
    ) {}

    public function load(string $runId): ?WorkflowState
    {
        $raw = $this->cache->get($this->prefix . $runId);
        if (!is_string($raw) || '' === $raw) {
            return null;
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return null;
        }

        /** @var array<string, mixed> $decoded */
        return WorkflowState::fromArray($decoded);
    }

    public function save(WorkflowState $state): void
    {
        $encoded = json_encode($state->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $this->cache->set($this->prefix . $state->runId, $encoded, $this->ttlSeconds);
    }

    public function delete(string $runId): void
    {
        $this->cache->delete($this->prefix . $runId);
    }
}
