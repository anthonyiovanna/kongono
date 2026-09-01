<?php

declare(strict_types=1);

namespace Kongono\AgentApplication\Generated;

use Kongono\AgentAdmission\Identity\AgentAdmissionIdentity;
use Kongono\AgentApplication\Profile\AgentApplicationProfileIdentity;
use Kongono\AgentDeclarationBuild\Declaration\AgentDeclarationIdentity;
use Kongono\AgentPrincipalContract\Capability\CapabilityIdentity;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Carries validator-reconstructed canonical generated-wiring evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class GeneratedAgentWiring
{
    /** @var list<CapabilityIdentity> */
    private array $permittedCapabilityIdentities;

    /**
     * @param list<CapabilityIdentity> $permittedCapabilityIdentities
     * @internal Constructed only from package-validated canonical bytes.
     */
    public function __construct(
        private AgentApplicationProfileIdentity $profileIdentity,
        private string $declarationArtifactSha256,
        private AgentDeclarationIdentity $declarationIdentity,
        private AgentAdmissionIdentity $admissionIdentity,
        array $permittedCapabilityIdentities,
        private string $implementationSha256,
        private string $generatedNamespace,
        private string $generatedClass,
        private string $canonicalBytes,
        private string $generatedWiringSha256,
    ) {
        $this->permittedCapabilityIdentities = [...$permittedCapabilityIdentities];
    }

    public function profileIdentity(): AgentApplicationProfileIdentity
    {
        return $this->profileIdentity;
    }

    public function declarationArtifactSha256(): string
    {
        return $this->declarationArtifactSha256;
    }

    public function declarationIdentity(): AgentDeclarationIdentity
    {
        return $this->declarationIdentity;
    }

    public function admissionIdentity(): AgentAdmissionIdentity
    {
        return $this->admissionIdentity;
    }

    /** @return list<CapabilityIdentity> */
    public function permittedCapabilityIdentities(): array
    {
        return [...$this->permittedCapabilityIdentities];
    }

    public function implementationSha256(): string
    {
        return $this->implementationSha256;
    }

    public function generatedNamespace(): string
    {
        return $this->generatedNamespace;
    }

    public function generatedClass(): string
    {
        return $this->generatedClass;
    }

    public function canonicalBytes(): string
    {
        return $this->canonicalBytes;
    }

    public function generatedWiringSha256(): string
    {
        return $this->generatedWiringSha256;
    }
}
