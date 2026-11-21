<?php

declare(strict_types=1);

namespace Kongono\Ai\Agent;

use Kongono\Ai\TokenUsage;

/** Auditable, secret-scrubbed receipt of a governed agent run. */
final readonly class AgentReceipt
{
    /**
     * @param list<array{tool: string, allowed: bool, summary: string}> $toolAudit
     */
    public function __construct(
        public string $principalId,
        public string $provider,
        public string $model,
        public string $finalText,
        public int $steps,
        public int $toolCallsExecuted,
        public array $toolAudit,
        public TokenUsage $usage,
    ) {}
}
