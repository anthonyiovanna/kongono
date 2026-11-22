<?php

declare(strict_types=1);

namespace Kongono\Jobs;

/** Atomic queue persistence boundary with lease expiration, generation fencing, deduplication, and dead-letter replay. */
interface QueueDriverInterface
{
    /**
     * Enqueue one JSON-serializable job (never PHP object serialization).
     * When `$uniqueKey` is provided and an active (`Pending` or `Reserved`) job in the same queue
     * already holds that key, returns the existing active job without enqueuing a duplicate.
     *
     * @param array<string, mixed> $payload
     */
    public function push(
        string $queue,
        string $type,
        array $payload = [],
        int $delaySeconds = 0,
        int $maxAttempts = 3,
        ?int $now = null,
        ?string $uniqueKey = null,
    ): Job;

    /** Atomically reserve the next due or lease-expired job, incrementing attempt & generation. */
    public function reserve(string $queue, int $leaseSeconds = 60, ?int $now = null): ?Job;

    /** Acknowledge completion only if generation & lease token still match. Returns false if stale. */
    public function acknowledge(Job $job): bool;

    /** Release job back to pending with backoff delay if generation & lease token still match. */
    public function releaseForRetry(Job $job, int $delaySeconds, ?string $error = null, ?int $now = null): bool;

    /** Mark job permanently failed / dead if generation & lease token still match. */
    public function markFailed(Job $job, JobStatus $terminalStatus, ?string $error = null): bool;

    /** @return array{pending: int, reserved: int, completed: int, failed: int, dead: int} */
    public function stats(string $queue): array;

    /** Delete completed/failed/dead jobs older than cutoff timestamp. */
    public function prune(string $queue, int $olderThanTimestamp): int;

    /**
     * List up to `$limit` failed or dead-lettered jobs in `$queue` (most recent first).
     *
     * @return list<Job>
     */
    public function listFailed(string $queue, int $limit = 100): array;

    /**
     * Replay one failed/dead job by ID (or all failed/dead jobs in `$queue` when `$jobId === null`)
     * back into `Pending` with attempt reset to 0.
     *
     * @return int Number of jobs requeued
     */
    public function retryFailed(string $queue, ?string $jobId = null, ?int $now = null): int;

    /**
     * Permanently delete all `Failed` and `Dead` jobs in `$queue`.
     *
     * @return int Number of jobs deleted
     */
    public function flushFailed(string $queue): int;
}
