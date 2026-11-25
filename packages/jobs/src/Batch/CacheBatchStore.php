<?php

declare(strict_types=1);

namespace Kongono\Jobs\Batch;

use Closure;
use Kongono\Core\Cache\AtomicCacheInterface;
use Kongono\Core\Cache\CacheInterface;
use Kongono\Jobs\JobException;

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
        return $this->withBatchLock($batchId, function () use ($batchId, $jobId): ?JobBatch {
            $batch = $this->find($batchId);
            if (null === $batch) {
                return null;
            }
            if (!$this->markJobRecorded($batchId, $jobId)) {
                return $batch;
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
        });
    }

    public function recordJobFailed(string $batchId, string $jobId): ?JobBatch
    {
        return $this->withBatchLock($batchId, function () use ($batchId, $jobId): ?JobBatch {
            $batch = $this->find($batchId);
            if (null === $batch) {
                return null;
            }
            if (!$this->markJobRecorded($batchId, $jobId)) {
                return $batch;
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
        });
    }

    public function cancel(string $batchId): ?JobBatch
    {
        return $this->withBatchLock($batchId, function () use ($batchId): ?JobBatch {
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
        });
    }

    /**
     * Guard read-modify-write batch updates with a short atomic mutex when backed by an AtomicCacheInterface.
     *
     * @template T
     * @param Closure(): T $fn
     * @return T
     */
    private function withBatchLock(string $batchId, Closure $fn): mixed
    {
        if (!$this->cache instanceof AtomicCacheInterface) {
            return $fn();
        }

        $lockKey = $this->prefix . 'lock:' . $batchId;
        $owner = bin2hex(random_bytes(16));
        $acquired = false;
        for ($i = 0; $i < 200; ++$i) {
            if ($this->cache->add($lockKey, $owner, 10)) {
                $acquired = true;
                break;
            }
            usleep(2000);
        }

        if (!$acquired) {
            throw new JobException(sprintf('Unable to acquire lock for job batch "%s".', $batchId));
        }

        try {
            return $fn();
        } finally {
            $this->cache->compareAndDelete($lockKey, $owner);
        }
    }

    /** Record a job ID as finalized for this batch; returns false if already recorded (deduplicates retries/reclaims). */
    private function markJobRecorded(string $batchId, string $jobId): bool
    {
        $doneKey = $this->key($batchId) . ':done';
        $raw = $this->cache->get($doneKey);
        /** @var array<string, bool> $done */
        $done = [];
        if (is_array($raw)) {
            foreach ($raw as $k => $v) {
                if (true === $v) {
                    $done[(string) $k] = true;
                }
            }
        }
        if (isset($done[$jobId])) {
            return false;
        }
        $done[$jobId] = true;
        $this->cache->set($doneKey, $done, $this->ttlSeconds);

        return true;
    }

    private function key(string $batchId): string
    {
        return $this->prefix . $batchId;
    }
}
