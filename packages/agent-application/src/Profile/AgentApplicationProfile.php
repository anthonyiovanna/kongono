<?php

declare(strict_types=1);

namespace Kongono\AgentApplication\Profile;

use Kongono\AgentAdmission\Authority\AgentAuthorityPlan;
use Kongono\AgentDeclarationBuild\Artifact\AgentDeclarationArtifact;
use Kongono\AgentDeclarationBuild\Declaration\AgentDeclaration;
use Kongono\AgentPrincipalContract\Capability\CapabilityIdentity;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Carries one exact declaration/artifact/plan binding as inert evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class AgentApplicationProfile
{
    /** @var list<CapabilityIdentity> */
    private array $permittedCapabilityIdentities;

    /**
     * @param list<CapabilityIdentity> $permittedCapabilityIdentities
     * @internal Constructed only by the package profile factory.
     */
    public function __construct(
        private AgentDeclaration $declaration,
        private AgentDeclarationArtifact $artifact,
        private AgentAuthorityPlan $authorityPlan,
        private AgentApplicationProfileIdentity $identity,
        array $permittedCapabilityIdentities,
    ) {
        $this->permittedCapabilityIdentities = [...$permittedCapabilityIdentities];
    }

    public function declaration(): AgentDeclaration
    {
        return $this->declaration;
    }

    public function declarationArtifact(): AgentDeclarationArtifact
    {
        return $this->artifact;
    }

    public function authorityPlan(): AgentAuthorityPlan
    {
        return $this->authorityPlan;
    }

    public function identity(): AgentApplicationProfileIdentity
    {
        return $this->identity;
    }

    /** @return list<CapabilityIdentity> */
    public function permittedCapabilityIdentities(): array
    {
        return [...$this->permittedCapabilityIdentities];
    }
}
