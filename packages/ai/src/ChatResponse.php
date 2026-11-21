<?php

declare(strict_types=1);

namespace Kongono\Ai;

final readonly class ChatResponse
{
    /** @param list<ToolCall> $toolCalls */
    public function __construct(
        public string $text,
        public string $model,
        public string $provider,
        public array $toolCalls = [],
        public TokenUsage $usage = new TokenUsage(),
        public ?string $finishReason = null,
    ) {}

    public function hasToolCalls(): bool
    {
        return [] !== $this->toolCalls;
    }
}
