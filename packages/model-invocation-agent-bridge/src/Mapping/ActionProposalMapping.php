<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationAgentBridge\Mapping;

use Kongono\AgentPrincipalContract\Capability\CapabilityRequest;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\Result\ActionName;

/** Holds one exact application-owned action mapping without granting authority. */
#[Api(ApiStatus::Experimental)]
final readonly class ActionProposalMapping
{
    public function __construct(
        private ActionName $expectedActionName,
        private CapabilityRequest $capabilityRequest,
        private ProposalUsageSelector $usageSelector,
    ) {}

    public function expectedActionName(): ActionName
    {
        return $this->expectedActionName;
    }

    public function capabilityRequest(): CapabilityRequest
    {
        return $this->capabilityRequest;
    }

    public function usageSelector(): ProposalUsageSelector
    {
        return $this->usageSelector;
    }
}
