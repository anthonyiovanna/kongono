<?php

declare(strict_types=1);

namespace Kongono\Jobs\Batch;

/** Deterministic in-memory batch store for unit tests and single-process apps. */
final class MemoryBatchStore implements BatchStoreInterface
{
    /** @var array<string, JobBatch> */
    private array $batches = [];

    public function create(JobBatch $batch): void
    {
        $this->batches[$batch->id] = $batch;
    }

    public function find(string $batchId): ?JobBatch
    {
        return $this->batches[$batchId] ?? null;
    }

    public function recordJobCompleted(string $batchId, string $jobId): ?JobBatch
    {
        $batch = $this->batches[$batchId] ?? null;
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
        $this->batches[$batchId] = $updated;

        return $updated;
    }

    public function recordJobFailed(string $batchId, string $jobId): ?JobBatch
    {
        $batch = $this->batches[$batchId] ?? null;
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
        $this->batches[$batchId] = $updated;

        return $updated;
    }

    public function cancel(string $batchId): ?JobBatch
    {
        $batch = $this->batches[$batchId] ?? null;
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
        $this->batches[$batchId] = $updated;

        return $updated;
    }
}
