<?php

declare(strict_types=1);

namespace Kongono\Ai;

final readonly class ChatRequest
{
    /**
     * @param list<Message>        $messages
     * @param list<ToolDefinition> $tools
     */
    public function __construct(
        public array $messages,
        public ?string $model = null,
        public ?float $temperature = null,
        public ?int $maxTokens = null,
        public array $tools = [],
        public bool $jsonMode = false,
    ) {}
}
