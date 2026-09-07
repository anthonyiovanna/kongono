<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationAgentBridge\Bridge;

use Kongono\AgentRuntime\Proposal\AgentProposalRequest;
use Kongono\AgentRuntime\Proposal\AgentProposalResult;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationAgentBridge\Exception\InvalidModelInvocationAgentBridgeException;
use Kongono\ModelInvocationAgentBridge\Exception\ProposalBridgeRejectedException;
use Kongono\ModelInvocationAgentBridge\Internal\BridgeGuard;
use Kongono\ModelInvocationAgentBridge\Internal\CanonicalEvidence;
use Kongono\ModelInvocationAgentBridge\Internal\ProposalFactory;
use Kongono\ModelInvocationAgentBridge\Internal\UsageProjector;
use Kongono\ModelInvocationAgentBridge\Internal\VisibilityProjector;
use Kongono\ModelInvocationAgentBridge\Mapping\ActionProposalMapping;
use Kongono\ModelInvocationRuntime\Execution\InvocationRuntimeResult;

/** Maps eligible normalized action evidence into one inert Agent proposal result. */
#[Api(ApiStatus::Experimental)]
final class ModelResultProposalBridge
{
    public function bridge(
        AgentProposalRequest $request,
        InvocationRuntimeResult $runtimeResult,
        ActionProposalMapping $mapping,
    ): AgentProposalResult {
        $result = BridgeGuard::eligibleResult($runtimeResult);
        $suggestion = $result->actionSuggestion();
        if (null === $suggestion) {
            throw InvalidModelInvocationAgentBridgeException::invalid('action_suggestion_required', 'bridge');
        }
        if (!$suggestion->name()->equals($mapping->expectedActionName())) {
            throw ProposalBridgeRejectedException::mapping('action_name_mismatch', 'mapping', count($request->permittedCapabilities()));
        }

        $permitted = $request->permittedCapabilities();
        $capabilityAccepted = false;
        foreach ($permitted as $identity) {
            if ($identity->equals($mapping->capabilityRequest()->identity())) {
                $capabilityAccepted = true;
                break;
            }
        }
        if (!$capabilityAccepted) {
            throw ProposalBridgeRejectedException::mapping('capability_not_permitted', 'capability', count($permitted));
        }

        $tokenUsage = UsageProjector::tokenUsage($result->usage(), $mapping->usageSelector());
        $outputByteUsage = UsageProjector::outputByteUsage($suggestion);
        $visibility = VisibilityProjector::project($result->visibility());
        $evidenceDigest = CanonicalEvidence::digest($result, $mapping, $tokenUsage, $outputByteUsage, $visibility);

        return ProposalFactory::create(
            $request,
            $result,
            $mapping,
            $visibility,
            $tokenUsage,
            $outputByteUsage,
            $evidenceDigest,
        );
    }
}
