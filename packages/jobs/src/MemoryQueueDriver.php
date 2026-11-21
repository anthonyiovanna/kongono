<?php

declare(strict_types=1);

namespace Kongono\Jobs;

/** Deterministic in-memory queue driver with lease expiry and generation fencing tokens. */
final class MemoryQueueDriver implements QueueDriverInterface
{
    /** @var array<string, Job> */
    private array $jobs = [];

    /** @var array<string, int> */
    private array $updatedAt = [];

    public function push(
        string $queue,
        string $type,
        array $payload = [],
        int $delaySeconds = 0,
        int $maxAttempts = 3,
        ?int $now = null,
    ): Job {
        $ts = $now ?? time();
        $id = bin2hex(random_bytes(16));
        $job = new Job(
            id: $id,
            queue: $queue,
            type: $type,
            payload: $payload,
            attempt: 0,
            maxAttempts: max(1, $maxAttempts),
            generation: 0,
            leaseToken: null,
            availableAt: $ts + max(0, $delaySeconds),
            leaseUntil: null,
            status: JobStatus::Pending,
        );
        $this->jobs[$id] = $job;
        $this->updatedAt[$id] = $ts;

        return $job;
    }

    public function reserve(string $queue, int $leaseSeconds = 60, ?int $now = null): ?Job
    {
        $ts = $now ?? time();

        foreach ($this->jobs as $id => $job) {
            if ($job->queue !== $queue) {
                continue;
            }
            $isDuePending = JobStatus::Pending === $job->status && $job->availableAt <= $ts;
            $isExpiredLease = JobStatus::Reserved === $job->status && null !== $job->leaseUntil && $job->leaseUntil <= $ts;

            if (!$isDuePending && !$isExpiredLease) {
                continue;
            }

            if ($job->attempt >= $job->maxAttempts) {
                $this->jobs[$id] = new Job(
                    id: $job->id,
                    queue: $job->queue,
                    type: $job->type,
                    payload: $job->payload,
                    attempt: $job->attempt,
                    maxAttempts: $job->maxAttempts,
                    generation: $job->generation + 1,
                    leaseToken: null,
                    availableAt: $job->availableAt,
                    leaseUntil: null,
                    status: JobStatus::Dead,
                    lastError: $job->lastError ?? 'Maximum attempts exhausted.',
                );
                $this->updatedAt[$id] = $ts;
                continue;
            }

            $reserved = new Job(
                id: $job->id,
                queue: $job->queue,
                type: $job->type,
                payload: $job->payload,
                attempt: $job->attempt + 1,
                maxAttempts: $job->maxAttempts,
                generation: $job->generation + 1,
                leaseToken: bin2hex(random_bytes(16)),
                availableAt: $job->availableAt,
                leaseUntil: $ts + max(1, $leaseSeconds),
                status: JobStatus::Reserved,
                lastError: $job->lastError,
            );
            $this->jobs[$id] = $reserved;
            $this->updatedAt[$id] = $ts;

            return $reserved;
        }

        return null;
    }

    public function acknowledge(Job $job): bool
    {
        if (!$this->ownsLease($job)) {
            return false;
        }
        $this->jobs[$job->id] = new Job(
            id: $job->id,
            queue: $job->queue,
            type: $job->type,
            payload: $job->payload,
            attempt: $job->attempt,
            maxAttempts: $job->maxAttempts,
            generation: $job->generation,
            leaseToken: null,
            availableAt: $job->availableAt,
            leaseUntil: null,
            status: JobStatus::Completed,
            lastError: null,
        );
        $this->updatedAt[$job->id] = time();

        return true;
    }

    public function releaseForRetry(Job $job, int $delaySeconds, ?string $error = null, ?int $now = null): bool
    {
        if (!$this->ownsLease($job)) {
            return false;
        }
        $ts = $now ?? time();
        $this->jobs[$job->id] = new Job(
            id: $job->id,
            queue: $job->queue,
            type: $job->type,
            payload: $job->payload,
            attempt: $job->attempt,
            maxAttempts: $job->maxAttempts,
            generation: $job->generation,
            leaseToken: null,
            availableAt: $ts + max(0, $delaySeconds),
            leaseUntil: null,
            status: JobStatus::Pending,
            lastError: $error,
        );
        $this->updatedAt[$job->id] = $ts;

        return true;
    }

    public function markFailed(Job $job, JobStatus $terminalStatus, ?string $error = null): bool
    {
        if (!$this->ownsLease($job)) {
            return false;
        }
        $this->jobs[$job->id] = new Job(
            id: $job->id,
            queue: $job->queue,
            type: $job->type,
            payload: $job->payload,
            attempt: $job->attempt,
            maxAttempts: $job->maxAttempts,
            generation: $job->generation,
            leaseToken: null,
            availableAt: $job->availableAt,
            leaseUntil: null,
            status: $terminalStatus,
            lastError: $error,
        );
        $this->updatedAt[$job->id] = time();

        return true;
    }

    public function stats(string $queue): array
    {
        $counts = ['pending' => 0, 'reserved' => 0, 'completed' => 0, 'failed' => 0, 'dead' => 0];
        foreach ($this->jobs as $job) {
            if ($job->queue === $queue) {
                ++$counts[$job->status->value];
            }
        }

        return $counts;
    }

    public function prune(string $queue, int $olderThanTimestamp): int
    {
        $removed = 0;
        foreach ($this->jobs as $id => $job) {
            if (
                $job->queue === $queue
                && in_array($job->status, [JobStatus::Completed, JobStatus::Failed, JobStatus::Dead], true)
                && ($this->updatedAt[$id] ?? PHP_INT_MAX) <= $olderThanTimestamp
            ) {
                unset($this->jobs[$id], $this->updatedAt[$id]);
                ++$removed;
            }
        }

        return $removed;
    }

    private function ownsLease(Job $job): bool
    {
        $current = $this->jobs[$job->id] ?? null;
        if (null === $current || JobStatus::Reserved !== $current->status) {
            return false;
        }
        if ($current->generation !== $job->generation) {
            return false;
        }

        return null !== $current->leaseToken && null !== $job->leaseToken && hash_equals($current->leaseToken, $job->leaseToken);
    }
}
