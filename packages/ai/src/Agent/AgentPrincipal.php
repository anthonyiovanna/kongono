<?php

declare(strict_types=1);

namespace Kongono\Ai\Agent;

/**
 * Governed Agent Principal: identity never grants implicit authority.
 * Every tool capability, human-approval gate, and execution/token/cost budget must be declared explicitly.
 */
final readonly class AgentPrincipal
{
    /**
     * @param list<string> $allowedTools          Explicit allowlist of permitted tool names
     * @param list<string> $approvalRequiredTools Subset of allowed tools that pause for human approval
     */
    public function __construct(
        public string $id,
        public string $role,
        public array $allowedTools = [],
        public int $maxSteps = 6,
        public int $maxToolCalls = 10,
        public array $approvalRequiredTools = [],
        public int $maxTokens = 0,
        public float $maxCostUsd = 0.0,
    ) {}

    public function allowsTool(string $toolName): bool
    {
        return in_array($toolName, $this->allowedTools, true);
    }

    public function requiresApproval(string $toolName): bool
    {
        return in_array($toolName, $this->approvalRequiredTools, true);
    }

    public function exceedsTokenBudget(int $totalTokens): bool
    {
        return $this->maxTokens > 0 && $totalTokens > $this->maxTokens;
    }

    public function exceedsCostBudget(float $costUsd): bool
    {
        return $this->maxCostUsd > 0.0 && $costUsd > $this->maxCostUsd;
    }
}
