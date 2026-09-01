<?php

declare(strict_types=1);

namespace Kongono\AgentPrincipalContract\Receipt;

use Kongono\AgentPrincipalContract\Identity\AgentPrincipalReference;
use Kongono\AgentPrincipalContract\Identity\OperationReference;
use Kongono\AgentPrincipalContract\Internal\ContractGuard;
use Kongono\AgentPrincipalContract\Proposal\ProposalIdentity;
use Kongono\AgentPrincipalContract\Provenance\AgentProvenance;
use Kongono\AgentPrincipalContract\Visibility\AgentVisibility;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Carries one bounded attributable claim without proving or authorizing an effect. */
#[Api(ApiStatus::Experimental)]
final readonly class AgentReceipt
{
    private string $evidenceSha256;

    public function __construct(
        private ReceiptIdentity $identity,
        private AgentPrincipalReference $principal,
        private OperationReference $operation,
        private ProposalIdentity $proposalIdentity,
        private ReceiptOutcome $outcome,
        string $evidenceSha256,
        private AgentProvenance $provenance,
        private AgentVisibility $visibility,
    ) {
        $this->evidenceSha256 = ContractGuard::receiptDigest($evidenceSha256);
    }

    public function identity(): ReceiptIdentity
    {
        return $this->identity;
    }

    public function principal(): AgentPrincipalReference
    {
        return $this->principal;
    }

    public function operation(): OperationReference
    {
        return $this->operation;
    }

    public function proposalIdentity(): ProposalIdentity
    {
        return $this->proposalIdentity;
    }

    public function outcome(): ReceiptOutcome
    {
        return $this->outcome;
    }

    public function evidenceSha256(): string
    {
        return $this->evidenceSha256;
    }

    public function provenance(): AgentProvenance
    {
        return $this->provenance;
    }

    public function visibility(): AgentVisibility
    {
        return $this->visibility;
    }
}
