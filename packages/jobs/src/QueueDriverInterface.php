<?php

declare(strict_types=1);

namespace Kongono\Jobs;

/** Atomic queue persistence boundary with lease expiration and generation fencing. */
interface QueueDriverInterface
{
    /**
     * Enqueue one JSON-serializable job (never PHP object serialization).
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
}
