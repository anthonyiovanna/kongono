<?php

declare(strict_types=1);

namespace Kongono\Jobs\Batch;

/** Immutable progress snapshot for a parallel fan-out/fan-in job batch. */
final readonly class JobBatch
{
    /**
     * @param list<string>         $failedJobIds
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string $id,
        public string $name,
        public int $totalJobs,
        public int $pendingJobs,
        public int $processedJobs,
        public int $failedJobs,
        public bool $cancelled,
        public bool $allowFailures,
        public int $createdAt,
        public ?int $finishedAt = null,
        public array $failedJobIds = [],
        public array $meta = [],
    ) {}

    /** Completion percentage from 0.0 to 100.0. */
    public function progress(): float
    {
        if ($this->totalJobs <= 0) {
            return 100.0;
        }

        $done = $this->totalJobs - $this->pendingJobs;

        return round(min(100.0, max(0.0, ($done / $this->totalJobs) * 100.0)), 2);
    }

    public function finished(): bool
    {
        return 0 === $this->pendingJobs || $this->cancelled || null !== $this->finishedAt;
    }

    public function hasFailures(): bool
    {
        return $this->failedJobs > 0;
    }

    public function succeeded(): bool
    {
        return $this->finished() && !$this->cancelled && 0 === $this->failedJobs;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'total_jobs' => $this->totalJobs,
            'pending_jobs' => $this->pendingJobs,
            'processed_jobs' => $this->processedJobs,
            'failed_jobs' => $this->failedJobs,
            'cancelled' => $this->cancelled,
            'allow_failures' => $this->allowFailures,
            'created_at' => $this->createdAt,
            'finished_at' => $this->finishedAt,
            'failed_job_ids' => $this->failedJobIds,
            'meta' => $this->meta,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        /** @var list<string> $failedIds */
        $failedIds = [];
        if (is_array($data['failed_job_ids'] ?? null)) {
            foreach ($data['failed_job_ids'] as $fid) {
                if (is_string($fid)) {
                    $failedIds[] = $fid;
                }
            }
        }

        /** @var array<string, mixed> $meta */
        $meta = [];
        if (is_array($data['meta'] ?? null)) {
            foreach ($data['meta'] as $k => $v) {
                $meta[(string) $k] = $v;
            }
        }

        return new self(
            id: is_string($data['id'] ?? null) ? $data['id'] : '',
            name: is_string($data['name'] ?? null) ? $data['name'] : '',
            totalJobs: is_int($data['total_jobs'] ?? null) ? $data['total_jobs'] : 0,
            pendingJobs: is_int($data['pending_jobs'] ?? null) ? $data['pending_jobs'] : 0,
            processedJobs: is_int($data['processed_jobs'] ?? null) ? $data['processed_jobs'] : 0,
            failedJobs: is_int($data['failed_jobs'] ?? null) ? $data['failed_jobs'] : 0,
            cancelled: (bool) ($data['cancelled'] ?? false),
            allowFailures: (bool) ($data['allow_failures'] ?? true),
            createdAt: is_int($data['created_at'] ?? null) ? $data['created_at'] : 0,
            finishedAt: is_int($data['finished_at'] ?? null) ? $data['finished_at'] : null,
            failedJobIds: $failedIds,
            meta: $meta,
        );
    }
}
