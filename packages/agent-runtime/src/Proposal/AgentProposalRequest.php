<?php

declare(strict_types=1);

namespace Kongono\AgentRuntime\Proposal;

use Kongono\AgentPrincipalContract\Capability\CapabilityIdentity;
use Kongono\AgentPrincipalContract\Constraint\AgentConstraint;
use Kongono\AgentPrincipalContract\Evidence\EvidenceReference;
use Kongono\AgentPrincipalContract\Identity\AgentPrincipalReference;
use Kongono\AgentPrincipalContract\Identity\OperationReference;
use Kongono\AgentPrincipalContract\Intent\AgentIntent;
use Kongono\AgentRuntime\Internal\RuntimeGuard;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Gives the proposer only exact bounded operation evidence, never the authority plan. */
#[Api(ApiStatus::Experimental)]
final readonly class AgentProposalRequest
{
    /** @var list<CapabilityIdentity> */
    private array $permittedCapabilities;
    /** @var list<AgentConstraint> */
    private array $constraints;
    /** @var list<EvidenceReference> */
    private array $evidenceReferences;

    /**
     * @param array<array-key, mixed> $permittedCapabilities
     * @param array<array-key, mixed> $constraints
     * @param array<array-key, mixed> $evidenceReferences
     */
    public function __construct(
        private AgentPrincipalReference $principal,
        private OperationReference $operation,
        private AgentIntent $intent,
        array $permittedCapabilities,
        array $constraints,
        array $evidenceReferences,
    ) {
        $this->permittedCapabilities = RuntimeGuard::capabilityIdentities($permittedCapabilities, 'proposal_capabilities');
        $this->constraints = RuntimeGuard::constraints($constraints, 'proposal_constraints');
        $this->evidenceReferences = RuntimeGuard::evidenceReferences($evidenceReferences, 'proposal_evidence');
    }

    public function principal(): AgentPrincipalReference
    {
        return $this->principal;
    }
    public function operation(): OperationReference
    {
        return $this->operation;
    }
    public function intent(): AgentIntent
    {
        return $this->intent;
    }
    /** @return list<CapabilityIdentity> */
    public function permittedCapabilities(): array
    {
        return [...$this->permittedCapabilities];
    }
    /** @return list<AgentConstraint> */
    public function constraints(): array
    {
        return [...$this->constraints];
    }
    /** @return list<EvidenceReference> */
    public function evidenceReferences(): array
    {
        return [...$this->evidenceReferences];
    }
}
