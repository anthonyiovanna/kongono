<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationAgentBridge\Internal;

use Kongono\AgentPrincipalContract\Proposal\ActionProposal;
use Kongono\AgentPrincipalContract\Proposal\ProposalIdentity;
use Kongono\AgentPrincipalContract\Provenance\AgentProvenance;
use Kongono\AgentPrincipalContract\Provenance\ProvenanceSource;
use Kongono\AgentPrincipalContract\Visibility\AgentVisibility;
use Kongono\AgentRuntime\Proposal\AgentProposalRequest;
use Kongono\AgentRuntime\Proposal\AgentProposalResult;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationAgentBridge\Mapping\ActionProposalMapping;
use Kongono\ModelInvocationContract\Result\ModelResult;

/** Creates inert Phase 13 proposal evidence from already validated projections. */
#[Api(ApiStatus::Internal)]
final class ProposalFactory
{
    public static function create(
        AgentProposalRequest $request,
        ModelResult $result,
        ActionProposalMapping $mapping,
        AgentVisibility $visibility,
        int $tokenUsage,
        int $outputByteUsage,
        string $evidenceDigest,
    ): AgentProposalResult {
        $proposal = new ActionProposal(
            new ProposalIdentity('model-proposal.' . $evidenceDigest),
            $request->principal(),
            $request->operation(),
            $request->intent(),
            $mapping->capabilityRequest(),
            $request->constraints(),
            $request->evidenceReferences(),
            new AgentProvenance(ProvenanceSource::Model, $result->identity()->value(), $evidenceDigest),
            $visibility,
        );

        return new AgentProposalResult($proposal, $tokenUsage, $outputByteUsage, $evidenceDigest);
    }

    private function __construct() {}
}
