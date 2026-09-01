<?php

declare(strict_types=1);

namespace Kongono\AgentRuntime\Capability;

use Kongono\AgentPrincipalContract\Capability\CapabilityIdentity;
use Kongono\AgentPrincipalContract\Identity\OperationReference;
use Kongono\AgentPrincipalContract\Intent\AgentIntent;
use Kongono\AgentPrincipalContract\Proposal\ActionProposal;
use Kongono\AgentRuntime\Budget\AgentRuntimeBudget;
use Kongono\AgentRuntime\Budget\AgentRuntimeUsage;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Exposes only the exact validated proposal and current bounded operation evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class AgentCapabilityInvocation
{
    public function __construct(
        private ActionProposal $proposal,
        private OperationReference $operation,
        private AgentIntent $intent,
        private CapabilityIdentity $capabilityIdentity,
        private AgentRuntimeBudget $budget,
        private AgentRuntimeUsage $usage,
    ) {}

    public function proposal(): ActionProposal
    {
        return $this->proposal;
    }
    public function operation(): OperationReference
    {
        return $this->operation;
    }
    public function intent(): AgentIntent
    {
        return $this->intent;
    }
    public function capabilityIdentity(): CapabilityIdentity
    {
        return $this->capabilityIdentity;
    }
    public function budget(): AgentRuntimeBudget
    {
        return $this->budget;
    }
    public function usage(): AgentRuntimeUsage
    {
        return $this->usage;
    }
}
