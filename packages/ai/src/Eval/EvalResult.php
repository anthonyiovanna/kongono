<?php

declare(strict_types=1);

namespace Kongono\Ai\Eval;

/** Outcome of a single AI eval case or adversarial red-team vector. */
final readonly class EvalResult
{
    /** @param list<string> $failures */
    public function __construct(
        public string $name,
        public bool $passed,
        public array $failures = [],
        public float $durationMs = 0.0,
        public int $totalTokens = 0,
        public string $outputExcerpt = '',
        public bool $canarySafe = true,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'passed' => $this->passed,
            'failures' => $this->failures,
            'duration_ms' => round($this->durationMs, 2),
            'total_tokens' => $this->totalTokens,
            'canary_safe' => $this->canarySafe,
            'output_excerpt' => $this->outputExcerpt,
        ];
    }
}
