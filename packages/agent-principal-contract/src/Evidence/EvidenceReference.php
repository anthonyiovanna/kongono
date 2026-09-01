<?php

declare(strict_types=1);

namespace Kongono\AgentPrincipalContract\Evidence;

use Kongono\AgentPrincipalContract\Internal\ContractGuard;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** References caller-supplied evidence without parsing, validating, or accepting it. */
#[Api(ApiStatus::Experimental)]
final readonly class EvidenceReference
{
    private string $sha256;

    public function __construct(
        private EvidenceKind $kind,
        private EvidenceIdentity $identity,
        string $sha256,
    ) {
        $this->sha256 = ContractGuard::evidenceDigest($sha256);
    }

    public function kind(): EvidenceKind
    {
        return $this->kind;
    }

    public function identity(): EvidenceIdentity
    {
        return $this->identity;
    }

    public function sha256(): string
    {
        return $this->sha256;
    }
}
