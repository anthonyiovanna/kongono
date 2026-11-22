<?php

declare(strict_types=1);

namespace Kongono\Ai\Eval;

/** Aggregated summary of an AI evaluation or red-team suite run. */
final readonly class EvalReport
{
    /** @param list<EvalResult> $results */
    public function __construct(
        public bool $passed,
        public int $total,
        public int $passedCount,
        public int $failedCount,
        public float $passRate,
        public int $totalTokens,
        public float $totalDurationMs,
        public array $results,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'passed' => $this->passed,
            'total' => $this->total,
            'passed_count' => $this->passedCount,
            'failed_count' => $this->failedCount,
            'pass_rate' => round($this->passRate, 2),
            'total_tokens' => $this->totalTokens,
            'total_duration_ms' => round($this->totalDurationMs, 2),
            'results' => array_map(static fn(EvalResult $r): array => $r->toArray(), $this->results),
        ];
    }
}
