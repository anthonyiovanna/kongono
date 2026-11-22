<?php

declare(strict_types=1);

namespace Kongono\Jobs\Batch;

use Kongono\Jobs\Queue;

/** Dispatches a parallel fan-out batch of jobs onto a {@see Queue} and tracks progress in a {@see BatchStoreInterface}. */
final readonly class BatchDispatcher
{
    public function __construct(
        private Queue $queue,
        private BatchStoreInterface $store,
    ) {}

    /**
     * Dispatch a parallel batch of jobs.
     *
     * @param list<array{type: string, payload?: array<string, mixed>, delaySeconds?: int, maxAttempts?: int, uniqueKey?: ?string}> $jobs
     * @param array<string, mixed>                                                                                                   $meta
     */
    public function dispatch(
        string $name,
        array $jobs,
        ?string $queue = null,
        bool $allowFailures = true,
        array $meta = [],
    ): JobBatch {
        $batchId = 'batch_' . bin2hex(random_bytes(12));
        $now = time();
        $total = count($jobs);

        $batch = new JobBatch(
            id: $batchId,
            name: $name,
            totalJobs: $total,
            pendingJobs: $total,
            processedJobs: 0,
            failedJobs: 0,
            cancelled: false,
            allowFailures: $allowFailures,
            createdAt: $now,
            finishedAt: 0 === $total ? $now : null,
            failedJobIds: [],
            meta: $meta,
        );
        $this->store->create($batch);

        foreach ($jobs as $spec) {
            $payload = $spec['payload'] ?? [];
            $payload['_batch_id'] = $batchId;

            $this->queue->dispatch(
                type: $spec['type'],
                payload: $payload,
                queue: $queue,
                delaySeconds: $spec['delaySeconds'] ?? 0,
                maxAttempts: $spec['maxAttempts'] ?? 3,
                uniqueKey: $spec['uniqueKey'] ?? null,
            );
        }

        return $batch;
    }

    public function find(string $batchId): ?JobBatch
    {
        return $this->store->find($batchId);
    }

    public function cancel(string $batchId): ?JobBatch
    {
        return $this->store->cancel($batchId);
    }
}
