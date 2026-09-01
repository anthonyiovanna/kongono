<?php

declare(strict_types=1);

namespace Kongono\AgentApplication\Runtime;

use Kongono\AgentApplication\Exception\AgentApplicationOperationException;
use Kongono\AgentPrincipalContract\Identity\OperationReference;
use Kongono\AgentPrincipalContract\Intent\AgentIntent;
use Kongono\AgentRuntime\Authority\AgentAuthorityCurrentness;
use Kongono\AgentRuntime\Budget\AgentRuntimeBudget;
use Kongono\AgentRuntime\Cancellation\AgentCancellation;
use Kongono\AgentRuntime\Capability\AgentCapabilityPort;
use Kongono\AgentRuntime\Clock\AgentMonotonicClock;
use Kongono\AgentRuntime\Proposal\AgentProposalPort;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Carries every explicit operation collaborator without discovery or retention. */
#[Api(ApiStatus::Experimental)]
final readonly class AgentApplicationRunInput
{
    /** @var list<AgentCapabilityPort> */
    private array $capabilityPorts;

    /** @param array<array-key, mixed> $capabilityPorts */
    public function __construct(
        private OperationReference $operation,
        private AgentIntent $intent,
        private AgentRuntimeBudget $budget,
        private AgentProposalPort $proposalPort,
        array $capabilityPorts,
        private AgentAuthorityCurrentness $authorityCurrentness,
        private AgentCancellation $cancellation,
        private AgentMonotonicClock $clock,
    ) {
        if (!array_is_list($capabilityPorts)) {
            throw AgentApplicationOperationException::failed('input');
        }
        foreach ($capabilityPorts as $port) {
            if (!$port instanceof AgentCapabilityPort) {
                throw AgentApplicationOperationException::failed('input');
            }
        }
        /** @var list<AgentCapabilityPort> $capabilityPorts */
        $this->capabilityPorts = [...$capabilityPorts];
    }

    public function operation(): OperationReference
    {
        return $this->operation;
    }
    public function intent(): AgentIntent
    {
        return $this->intent;
    }
    public function budget(): AgentRuntimeBudget
    {
        return $this->budget;
    }
    public function proposalPort(): AgentProposalPort
    {
        return $this->proposalPort;
    }
    /** @return list<AgentCapabilityPort> */
    public function capabilityPorts(): array
    {
        return [...$this->capabilityPorts];
    }
    public function authorityCurrentness(): AgentAuthorityCurrentness
    {
        return $this->authorityCurrentness;
    }
    public function cancellation(): AgentCancellation
    {
        return $this->cancellation;
    }
    public function clock(): AgentMonotonicClock
    {
        return $this->clock;
    }
}
