<?php

declare(strict_types=1);

namespace Kongono\AgentRuntime\Runtime;

use Kongono\AgentPrincipalContract\Proposal\ActionProposal;
use Kongono\AgentPrincipalContract\Receipt\AgentReceipt;
use Kongono\AgentRuntime\Budget\AgentRuntimeUsage;
use Kongono\AgentRuntime\Capability\AgentCapabilityResult;
use Kongono\AgentRuntime\Internal\RuntimeGuard;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Returns bounded operation evidence after all owned cleanup succeeds. */
#[Api(ApiStatus::Experimental)]
final readonly class AgentRuntimeResult
{
    /** @var list<AgentReceipt> */
    private array $receipts;

    /** @param array<array-key, mixed> $receipts */
    public function __construct(
        private AgentRuntimeOutcome $outcome,
        private AgentRuntimeState $terminalState,
        private AgentRuntimeUsage $usage,
        private ?ActionProposal $validatedProposal,
        private ?AgentCapabilityResult $capabilityResult,
        array $receipts,
        private bool $cleanupCompletedWithoutFailure,
    ) {
        $this->receipts = RuntimeGuard::receipts($receipts);
    }

    public function outcome(): AgentRuntimeOutcome
    {
        return $this->outcome;
    }
    public function terminalState(): AgentRuntimeState
    {
        return $this->terminalState;
    }
    public function usage(): AgentRuntimeUsage
    {
        return $this->usage;
    }
    public function validatedProposal(): ?ActionProposal
    {
        return $this->validatedProposal;
    }
    public function capabilityResult(): ?AgentCapabilityResult
    {
        return $this->capabilityResult;
    }
    /** @return list<AgentReceipt> */
    public function receipts(): array
    {
        return [...$this->receipts];
    }
    public function cleanupCompletedWithoutFailure(): bool
    {
        return $this->cleanupCompletedWithoutFailure;
    }
}
