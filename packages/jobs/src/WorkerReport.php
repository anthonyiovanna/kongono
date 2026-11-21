<?php

declare(strict_types=1);

namespace Kongono\Jobs;

final readonly class WorkerReport
{
    public function __construct(
        public int $processed,
        public int $completed,
        public int $retried,
        public int $failed,
        public int $uncertain,
        public int $stale,
    ) {}
}
