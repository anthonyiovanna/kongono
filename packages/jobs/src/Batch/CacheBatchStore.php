<?php

declare(strict_types=1);

namespace Kongono\Jobs\Batch;

use Kongono\Core\Cache\CacheInterface;

/** Cache-backed batch store (works with MemoryCache, FileCache, or shared RedisCache across workers). */
final readonly class CacheBatchStore implements BatchStoreInterface
{
    public function __construct(
        private CacheInterface $cache,
        private string $prefix = 'kongono:batch:',
        private int $ttlSeconds = 604_800,
    ) {}

    public function create(JobBatch $batch): void
    {
        $this->cache->set($this->key($batch->id), $batch->toArray(), $this->ttlSeconds);
    }

    public function find(string $batchId): ?JobBatch
    {
        $raw = $this->cache->get($this->key($batchId));
        if (!is_array($raw)) {
            return null;
        }

        /** @var array<string, mixed> $normalized */
        $normalized = [];
        foreach ($raw as $k => $v) {
            $normalized[(string) $k] = $v;
        }

        return JobBatch::fromArray($normalized);
    }

    public function recordJobCompleted(string $batchId, string $jobId): ?JobBatch
    {
        $batch = $this->find($batchId);
        if (null === $batch) {
            return null;
        }

        $pending = max(0, $batch->pendingJobs - 1);
        $updated = new JobBatch(
            id: $batch->id,
            name: $batch->name,
            totalJobs: $batch->totalJobs,
            pendingJobs: $pending,
            processedJobs: $batch->processedJobs + 1,
            failedJobs: $batch->failedJobs,
            cancelled: $batch->cancelled,
            allowFailures: $batch->allowFailures,
            createdAt: $batch->createdAt,
            finishedAt: 0 === $pending ? ($batch->finishedAt ?? time()) : $batch->finishedAt,
            failedJobIds: $batch->failedJobIds,
            meta: $batch->meta,
        );
        $this->create($updated);

        return $updated;
    }

    public function recordJobFailed(string $batchId, string $jobId): ?JobBatch
    {
        $batch = $this->find($batchId);
        if (null === $batch) {
            return null;
        }

        $pending = max(0, $batch->pendingJobs - 1);
        $failedIds = $batch->failedJobIds;
        if (!in_array($jobId, $failedIds, true)) {
            $failedIds[] = $jobId;
        }
        $cancelled = $batch->cancelled || !$batch->allowFailures;

        $updated = new JobBatch(
            id: $batch->id,
            name: $batch->name,
            totalJobs: $batch->totalJobs,
            pendingJobs: $pending,
            processedJobs: $batch->processedJobs,
            failedJobs: $batch->failedJobs + 1,
            cancelled: $cancelled,
            allowFailures: $batch->allowFailures,
            createdAt: $batch->createdAt,
            finishedAt: (0 === $pending || $cancelled) ? ($batch->finishedAt ?? time()) : $batch->finishedAt,
            failedJobIds: $failedIds,
            meta: $batch->meta,
        );
        $this->create($updated);

        return $updated;
    }

    public function cancel(string $batchId): ?JobBatch
    {
        $batch = $this->find($batchId);
        if (null === $batch) {
            return null;
        }

        $updated = new JobBatch(
            id: $batch->id,
            name: $batch->name,
            totalJobs: $batch->totalJobs,
            pendingJobs: $batch->pendingJobs,
            processedJobs: $batch->processedJobs,
            failedJobs: $batch->failedJobs,
            cancelled: true,
            allowFailures: $batch->allowFailures,
            createdAt: $batch->createdAt,
            finishedAt: $batch->finishedAt ?? time(),
            failedJobIds: $batch->failedJobIds,
            meta: $batch->meta,
        );
        $this->create($updated);

        return $updated;
    }

    private function key(string $batchId): string
    {
        return $this->prefix . $batchId;
    }
}
