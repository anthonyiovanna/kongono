<?php

declare(strict_types=1);

namespace Kongono\Jobs;

/** Immutable job snapshot carrying explicit JSON payload and lease fencing token. */
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
    ) {}
}
