<?php

declare(strict_types=1);

namespace Kongono\AgentPrincipalContract\Provenance;

use Kongono\AgentPrincipalContract\Internal\ContractGuard;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Carries bounded source evidence without establishing currentness or truth. */
#[Api(ApiStatus::Experimental)]
final readonly class AgentProvenance
{
    private string $sourceIdentity;
    private string $sha256;

    public function __construct(
        private ProvenanceSource $source,
        string $sourceIdentity,
        string $sha256,
    ) {
        $this->sourceIdentity = ContractGuard::provenanceIdentity($sourceIdentity);
        $this->sha256 = ContractGuard::provenanceDigest($sha256);
    }

    public function source(): ProvenanceSource
    {
        return $this->source;
    }

    public function sourceIdentity(): string
    {
        return $this->sourceIdentity;
    }

    public function sha256(): string
    {
        return $this->sha256;
    }
}
