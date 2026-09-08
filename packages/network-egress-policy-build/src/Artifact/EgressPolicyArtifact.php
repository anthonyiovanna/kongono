<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressPolicyBuild\Artifact;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressContract\Policy\EgressPolicyReference;
use Kongono\NetworkEgressPolicyBuild\Policy\EgressPolicyIdentity;

/** Stores only builder- or validator-verified detached policy evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class EgressPolicyArtifact
{
    /** @internal Constructed only from package-verified canonical state. */
    public function __construct(
        private string $canonicalBytes,
        private string $artifactSha256,
        private string $sourceSha256,
        private EgressPolicyIdentity $policyIdentity,
    ) {}

    public function canonicalBytes(): string
    {
        return $this->canonicalBytes;
    }

    public function artifactSha256(): string
    {
        return $this->artifactSha256;
    }

    public function sourceSha256(): string
    {
        return $this->sourceSha256;
    }

    public function policyIdentity(): EgressPolicyIdentity
    {
        return $this->policyIdentity;
    }

    public function policyReference(): EgressPolicyReference
    {
        return new EgressPolicyReference($this->policyIdentity->value(), strtoupper($this->sourceSha256));
    }
}
