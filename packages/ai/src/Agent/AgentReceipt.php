<?php

declare(strict_types=1);

namespace Kongono\Ai\Agent;

use Kongono\Ai\TokenUsage;
use Kongono\Ai\ToolCall;

/** Auditable, secret-scrubbed receipt of a governed agent run (completed or paused awaiting human approval). */
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
        public string $status = 'completed',
        public ?ToolCall $pendingApproval = null,
    ) {}

    public function isAwaitingApproval(): bool
    {
        return 'awaiting_approval' === $this->status && null !== $this->pendingApproval;
    }
}
