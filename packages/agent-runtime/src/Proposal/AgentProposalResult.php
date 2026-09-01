<?php

declare(strict_types=1);

namespace Kongono\AgentRuntime\Proposal;

use Kongono\AgentPrincipalContract\Proposal\ActionProposal;
use Kongono\AgentRuntime\Internal\RuntimeGuard;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Carries one proposal plus bounded adapter-reported usage and evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class AgentProposalResult
{
    private string $evidenceSha256;

    public function __construct(
        private ActionProposal $proposal,
        private int $tokenUsage,
        private int $outputByteUsage,
        string $evidenceSha256,
    ) {
        if ($tokenUsage < 0 || $outputByteUsage < 0) {
            throw \Kongono\AgentRuntime\Exception\InvalidAgentRuntimeException::invalid('reported_usage_range');
        }
        $this->evidenceSha256 = RuntimeGuard::digest($evidenceSha256);
    }

    public function proposal(): ActionProposal
    {
        return $this->proposal;
    }
    public function tokenUsage(): int
    {
        return $this->tokenUsage;
    }
    public function outputByteUsage(): int
    {
        return $this->outputByteUsage;
    }
    public function evidenceSha256(): string
    {
        return $this->evidenceSha256;
    }
}
