<?php

declare(strict_types=1);

namespace Kongono\Jobs;

/** Ergonomic job producer over any QueueDriverInterface. */
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
    ): Job {
        return $this->driver->push(
            queue: $queue ?? $this->defaultQueue,
            type: $type,
            payload: $payload,
            delaySeconds: $delaySeconds,
            maxAttempts: $maxAttempts,
        );
    }

    public function driver(): QueueDriverInterface
    {
        return $this->driver;
    }

    /** @return array{pending: int, reserved: int, completed: int, failed: int, dead: int} */
    public function stats(?string $queue = null): array
    {
        return $this->driver->stats($queue ?? $this->defaultQueue);
    }
}
