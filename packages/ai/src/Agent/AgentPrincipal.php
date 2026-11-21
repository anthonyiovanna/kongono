<?php

declare(strict_types=1);

namespace Kongono\Ai\Agent;

/**
 * Governed Agent Principal: identity never grants implicit authority.
 * Every tool capability and execution budget must be declared explicitly.
 */
final readonly class AgentPrincipal
{
    /** @param list<string> $allowedTools */
    public function __construct(
        public string $id,
        public string $role,
        public array $allowedTools = [],
        public int $maxSteps = 6,
        public int $maxToolCalls = 10,
    ) {}

    public function allowsTool(string $toolName): bool
    {
        return in_array($toolName, $this->allowedTools, true);
    }
}
