<?php

declare(strict_types=1);

namespace Kongono\Jobs;

/** Ergonomic job producer over any QueueDriverInterface (with optional uniqueKey deduplication & dead-letter helpers). */
final readonly class Queue
{
    public function __construct(
        private QueueDriverInterface $driver,
        private string $defaultQueue = 'default',
    ) {}

    /** @param array<string, mixed> $payload */
    public function dispatch(
        string $type,
        array $payload = [],
        ?string $queue = null,
        int $delaySeconds = 0,
        int $maxAttempts = 3,
        ?string $uniqueKey = null,
    ): Job {
        return $this->driver->push(
            queue: $queue ?? $this->defaultQueue,
            type: $type,
            payload: $payload,
            delaySeconds: $delaySeconds,
            maxAttempts: $maxAttempts,
            uniqueKey: $uniqueKey,
        );
    }

    public function driver(): QueueDriverInterface
    {
        return $this->driver;
    }

    public function defaultQueue(): string
    {
        return $this->defaultQueue;
    }

    /** @return array{pending: int, reserved: int, completed: int, failed: int, dead: int} */
    public function stats(?string $queue = null): array
    {
        return $this->driver->stats($queue ?? $this->defaultQueue);
    }

    /** @return list<Job> */
    public function listFailed(?string $queue = null, int $limit = 100): array
    {
        return $this->driver->listFailed($queue ?? $this->defaultQueue, $limit);
    }

    public function retryFailed(?string $jobId = null, ?string $queue = null): int
    {
        return $this->driver->retryFailed($queue ?? $this->defaultQueue, $jobId);
    }

    public function flushFailed(?string $queue = null): int
    {
        return $this->driver->flushFailed($queue ?? $this->defaultQueue);
    }
}
