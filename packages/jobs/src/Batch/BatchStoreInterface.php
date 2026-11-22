<?php

declare(strict_types=1);

namespace Kongono\Jobs\Batch;

/** Persistence boundary tracking parallel fan-out/fan-in job batch state. */
interface BatchStoreInterface
{
    public function create(JobBatch $batch): void;

    public function find(string $batchId): ?JobBatch;

    public function recordJobCompleted(string $batchId, string $jobId): ?JobBatch;

    public function recordJobFailed(string $batchId, string $jobId): ?JobBatch;

    public function cancel(string $batchId): ?JobBatch;
}
