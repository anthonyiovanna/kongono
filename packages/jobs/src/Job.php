<?php

declare(strict_types=1);

namespace Kongono\Jobs;

/** Immutable job snapshot carrying explicit JSON payload, optional unique key, and lease fencing token. */
final readonly class Job
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        public string $id,
        public string $queue,
        public string $type,
        public array $payload,
        public int $attempt,
        public int $maxAttempts,
        public int $generation,
        public ?string $leaseToken,
        public int $availableAt,
        public ?int $leaseUntil,
        public JobStatus $status,
        public ?string $lastError = null,
        public ?string $uniqueKey = null,
    ) {}

    public function batchId(): ?string
    {
        $raw = $this->payload['_batch_id'] ?? null;

        return is_string($raw) && '' !== $raw ? $raw : null;
    }
}
