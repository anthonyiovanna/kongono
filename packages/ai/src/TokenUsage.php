<?php

declare(strict_types=1);

namespace Kongono\Ai;

final readonly class TokenUsage
{
    public function __construct(
        public int $inputTokens = 0,
        public int $outputTokens = 0,
    ) {}

    public function totalTokens(): int
    {
        return $this->inputTokens + $this->outputTokens;
    }
}
