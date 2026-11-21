<?php

declare(strict_types=1);

namespace Kongono\Jobs;

use Kongono\Core\Cache\RedisClient;

/**
 * Zero-dependency Redis / Valkey distributed queue driver with delayed sorted sets,
 * lease expiration reclaim, and generation + lease-token fencing.
 */
final readonly class RedisQueueDriver implements QueueDriverInterface
{
    public function __construct(
        private RedisClient $redis,
        private string $prefix = 'kongono:queue:',
    ) {}

    public function push(
        string $queue,
        string $type,
        array $payload = [],
        int $delaySeconds = 0,
        int $maxAttempts = 3,
        ?int $now = null,
    ): Job {
        $ts = $now ?? time();
        $id = 'job_' . bin2hex(random_bytes(8));
        $availableAt = $ts + max(0, $delaySeconds);

        $job = new Job(
            id: $id,
            queue: $queue,
            type: $type,
            payload: $payload,
            attempt: 0,
            maxAttempts: max(1, $maxAttempts),
            generation: 0,
            leaseToken: null,
            availableAt: $availableAt,
            leaseUntil: null,
            status: JobStatus::Pending,
        );

        $this->saveJob($queue, $job);
        $this->redis->zAdd($this->key($queue, 'pending'), (float) $availableAt, $id);

        return $job;
    }

    public function reserve(string $queue, int $leaseSeconds = 60, ?int $now = null): ?Job
    {
        $ts = $now ?? time();
        $candidateId = null;

        $due = $this->redis->zRangeByScore($this->key($queue, 'pending'), '-inf', (string) $ts, 1);
        if ([] !== $due) {
            $candidateId = $due[0];
            $this->redis->zRem($this->key($queue, 'pending'), $candidateId);
        } else {
            $expired = $this->redis->zRangeByScore($this->key($queue, 'reserved'), '-inf', (string) $ts, 1);
            if ([] !== $expired) {
                $candidateId = $expired[0];
                $this->redis->zRem($this->key($queue, 'reserved'), $candidateId);
            }
        }

        if (null === $candidateId) {
            return null;
        }

        $current = $this->loadJob($queue, $candidateId);
        if (null === $current) {
            return null;
        }

        $leaseUntil = $ts + max(1, $leaseSeconds);
        $reserved = new Job(
            id: $current->id,
            queue: $current->queue,
            type: $current->type,
            payload: $current->payload,
            attempt: $current->attempt + 1,
            maxAttempts: $current->maxAttempts,
            generation: $current->generation + 1,
            leaseToken: bin2hex(random_bytes(12)),
            availableAt: $current->availableAt,
            leaseUntil: $leaseUntil,
            status: JobStatus::Reserved,
            lastError: $current->lastError,
        );

        $this->saveJob($queue, $reserved);
        $this->redis->zAdd($this->key($queue, 'reserved'), (float) $leaseUntil, $reserved->id);

        return $reserved;
    }

    public function acknowledge(Job $job): bool
    {
        $current = $this->loadJob($job->queue, $job->id);
        if (null === $current || !$this->fenceMatches($current, $job)) {
            return false;
        }

        $completed = new Job(
            id: $current->id,
            queue: $current->queue,
            type: $current->type,
            payload: $current->payload,
            attempt: $current->attempt,
            maxAttempts: $current->maxAttempts,
            generation: $current->generation,
            leaseToken: null,
            availableAt: $current->availableAt,
            leaseUntil: null,
            status: JobStatus::Completed,
            lastError: null,
        );

        $this->redis->zRem($this->key($job->queue, 'reserved'), $job->id);
        $this->saveJob($job->queue, $completed);
        $this->redis->zAdd($this->key($job->queue, 'terminal'), (float) time(), $job->id);

        return true;
    }

    public function releaseForRetry(Job $job, int $delaySeconds, ?string $error = null, ?int $now = null): bool
    {
        $current = $this->loadJob($job->queue, $job->id);
        if (null === $current || !$this->fenceMatches($current, $job)) {
            return false;
        }

        $nextAt = ($now ?? time()) + max(0, $delaySeconds);
        $retried = new Job(
            id: $current->id,
            queue: $current->queue,
            type: $current->type,
            payload: $current->payload,
            attempt: $current->attempt,
            maxAttempts: $current->maxAttempts,
            generation: $current->generation,
            leaseToken: null,
            availableAt: $nextAt,
            leaseUntil: null,
            status: JobStatus::Pending,
            lastError: $error,
        );

        $this->redis->zRem($this->key($job->queue, 'reserved'), $job->id);
        $this->saveJob($job->queue, $retried);
        $this->redis->zAdd($this->key($job->queue, 'pending'), (float) $nextAt, $job->id);

        return true;
    }

    public function markFailed(Job $job, JobStatus $terminalStatus, ?string $error = null): bool
    {
        $current = $this->loadJob($job->queue, $job->id);
        if (null === $current || !$this->fenceMatches($current, $job)) {
            return false;
        }

        $failed = new Job(
            id: $current->id,
            queue: $current->queue,
            type: $current->type,
            payload: $current->payload,
            attempt: $current->attempt,
            maxAttempts: $current->maxAttempts,
            generation: $current->generation,
            leaseToken: null,
            availableAt: $current->availableAt,
            leaseUntil: null,
            status: $terminalStatus,
            lastError: $error,
        );

        $this->redis->zRem($this->key($job->queue, 'reserved'), $job->id);
        $this->saveJob($job->queue, $failed);
        $this->redis->zAdd($this->key($job->queue, 'terminal'), (float) time(), $job->id);

        return true;
    }

    public function stats(string $queue): array
    {
        $counts = ['pending' => 0, 'reserved' => 0, 'completed' => 0, 'failed' => 0, 'dead' => 0];
        foreach ($this->redis->hGetAll($this->key($queue, 'jobs')) as $raw) {
            $job = $this->decodeJob($raw);
            if (null === $job) {
                continue;
            }
            ++$counts[$job->status->value];
        }

        return $counts;
    }

    public function prune(string $queue, int $olderThanTimestamp): int
    {
        $ids = $this->redis->zRangeByScore($this->key($queue, 'terminal'), '-inf', (string) $olderThanTimestamp);
        if ([] === $ids) {
            return 0;
        }

        $this->redis->zRem($this->key($queue, 'terminal'), ...$ids);
        $this->redis->hDel($this->key($queue, 'jobs'), ...$ids);

        return count($ids);
    }

    private function fenceMatches(Job $current, Job $claim): bool
    {
        return JobStatus::Reserved === $current->status
            && $current->generation === $claim->generation
            && null !== $current->leaseToken
            && null !== $claim->leaseToken
            && hash_equals($current->leaseToken, $claim->leaseToken);
    }

    private function saveJob(string $queue, Job $job): void
    {
        $encoded = json_encode([
            'id' => $job->id,
            'queue' => $job->queue,
            'type' => $job->type,
            'payload' => $job->payload,
            'attempt' => $job->attempt,
            'max_attempts' => $job->maxAttempts,
            'generation' => $job->generation,
            'lease_token' => $job->leaseToken,
            'available_at' => $job->availableAt,
            'lease_until' => $job->leaseUntil,
            'status' => $job->status->value,
            'last_error' => $job->lastError,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        $this->redis->hSet($this->key($queue, 'jobs'), $job->id, $encoded);
    }

    private function loadJob(string $queue, string $id): ?Job
    {
        $raw = $this->redis->hGet($this->key($queue, 'jobs'), $id);

        return null === $raw ? null : $this->decodeJob($raw);
    }

    private function decodeJob(string $raw): ?Job
    {
        $data = json_decode($raw, true);
        if (!is_array($data) || !is_string($data['id'] ?? null) || !is_string($data['queue'] ?? null) || !is_string($data['type'] ?? null)) {
            return null;
        }

        $statusVal = is_string($data['status'] ?? null) ? $data['status'] : 'pending';
        $status = JobStatus::tryFrom($statusVal) ?? JobStatus::Pending;
        /** @var array<string, mixed> $payload */
        $payload = is_array($data['payload'] ?? null) ? $data['payload'] : [];

        return new Job(
            id: $data['id'],
            queue: $data['queue'],
            type: $data['type'],
            payload: $payload,
            attempt: is_int($data['attempt'] ?? null) ? $data['attempt'] : 0,
            maxAttempts: is_int($data['max_attempts'] ?? null) ? $data['max_attempts'] : 3,
            generation: is_int($data['generation'] ?? null) ? $data['generation'] : 0,
            leaseToken: is_string($data['lease_token'] ?? null) ? $data['lease_token'] : null,
            availableAt: is_int($data['available_at'] ?? null) ? $data['available_at'] : 0,
            leaseUntil: is_int($data['lease_until'] ?? null) ? $data['lease_until'] : null,
            status: $status,
            lastError: is_string($data['last_error'] ?? null) ? $data['last_error'] : null,
        );
    }

    private function key(string $queue, string $suffix): string
    {
        return $this->prefix . $queue . ':' . $suffix;
    }
}
