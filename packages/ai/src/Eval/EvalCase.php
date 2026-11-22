<?php

declare(strict_types=1);

namespace Kongono\Ai\Eval;

/** Declarative AI evaluation case asserting accuracy, DTO validity, latency/token budgets, and PromptGuard safety. */
final readonly class EvalCase
{
    /**
     * @param list<string>      $expectedSubstrings  Case-insensitive substrings that MUST appear in the model output
     * @param list<string>      $forbiddenSubstrings Case-insensitive substrings that MUST NOT appear in the model output
     * @param class-string|null $structuredDtoClass  Optional readonly DTO class to validate via AiClient::structured()
     */
    public function __construct(
        public string $name,
        public string $prompt,
        public string $systemPrompt = 'You are a concise, accurate Kongono assistant.',
        public array $expectedSubstrings = [],
        public array $forbiddenSubstrings = [],
        public ?string $structuredDtoClass = null,
        public ?int $maxTokens = null,
        public ?float $maxLatencyMs = null,
        public bool $wrapAsUntrusted = false,
        public bool $enablePromptGuard = true,
    ) {}
}
