<?php

declare(strict_types=1);

namespace Kongono\AgentRuntime\Runtime;

use Kongono\AgentAdmission\Authority\AgentAuthorityPlan;
use Kongono\AgentDeclarationBuild\Declaration\AgentDeclaration;
use Kongono\AgentPrincipalContract\Capability\CapabilityIdentity;
use Kongono\AgentPrincipalContract\Identity\OperationReference;
use Kongono\AgentPrincipalContract\Intent\AgentIntent;
use Kongono\AgentRuntime\Authority\AgentAuthorityCurrentness;
use Kongono\AgentRuntime\Budget\AgentRuntimeBudget;
use Kongono\AgentRuntime\Cancellation\AgentCancellation;
use Kongono\AgentRuntime\Capability\AgentCapabilityPort;
use Kongono\AgentRuntime\Clock\AgentMonotonicClock;
use Kongono\AgentRuntime\Internal\RuntimeGuard;
use Kongono\AgentRuntime\Proposal\AgentProposalPort;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Aggregates one explicit operation and validates its canonical supplied ports. */
#[Api(ApiStatus::Experimental)]
final readonly class AgentRuntimeRequest
{
    /** @var list<AgentCapabilityPort> */
    private array $capabilityPorts;
    /** @var list<CapabilityIdentity> */
    private array $capabilityIdentities;

    /** @param array<array-key, mixed> $capabilityPorts */
    public function __construct(
        private AgentDeclaration $declaration,
        private AgentAuthorityPlan $authorityPlan,
        private OperationReference $operation,
        private AgentIntent $intent,
        private AgentRuntimeBudget $budget,
        private AgentProposalPort $proposalPort,
        array $capabilityPorts,
        private AgentAuthorityCurrentness $authorityCurrentness,
        private AgentCancellation $cancellation,
        private AgentMonotonicClock $clock,
    ) {
        [$ports, $identities] = RuntimeGuard::capabilityPorts($capabilityPorts, $declaration, $authorityPlan);
        $this->capabilityPorts = $ports;
        $this->capabilityIdentities = $identities;
    }

    public function declaration(): AgentDeclaration
    {
        return $this->declaration;
    }
    public function authorityPlan(): AgentAuthorityPlan
    {
        return $this->authorityPlan;
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
    /** @return list<CapabilityIdentity> @internal */
    public function capabilityIdentities(): array
    {
        return [...$this->capabilityIdentities];
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
