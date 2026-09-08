<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressApplication\Generated;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressApplication\Profile\NetworkEgressApplicationProfileIdentity;
use Kongono\NetworkEgressPolicyBuild\Policy\EgressPolicyIdentity;

/** Carries validator-reconstructed canonical generated-wiring evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class GeneratedNetworkEgressWiring
{
    /** @internal Constructed only from package-validated canonical bytes. */
    public function __construct(
        private NetworkEgressApplicationProfileIdentity $applicationProfileIdentity,
        private EgressPolicyIdentity $policyIdentity,
        private string $artifactSha256,
        private string $sourceSha256,
        private string $requiredCapabilitiesSha256,
        private string $implementationSha256,
        private string $generatedNamespace,
        private string $generatedClass,
        private string $canonicalBytes,
        private string $generatedWiringSha256,
    ) {}

    public function applicationProfileIdentity(): NetworkEgressApplicationProfileIdentity
    {
        return $this->applicationProfileIdentity;
    }

    public function policyIdentity(): EgressPolicyIdentity
    {
        return $this->policyIdentity;
    }

    public function artifactSha256(): string
    {
        return $this->artifactSha256;
    }

    public function sourceSha256(): string
    {
        return $this->sourceSha256;
    }

    public function requiredCapabilitiesSha256(): string
    {
        return $this->requiredCapabilitiesSha256;
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
