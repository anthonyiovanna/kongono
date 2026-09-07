<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationAgentBridge\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationAgentBridge\Exception\ProposalBridgeRejectedException;
use Kongono\ModelInvocationContract\Cost\CostEvidence;
use Kongono\ModelInvocationContract\Invocation\ResultMode;
use Kongono\ModelInvocationContract\Lifecycle\InvocationState;
use Kongono\ModelInvocationContract\Receipt\InvocationOutcome;
use Kongono\ModelInvocationContract\Result\FinishReason;
use Kongono\ModelInvocationContract\Result\ModelResult;
use Kongono\ModelInvocationContract\Usage\UsageEvidence;
use Kongono\ModelInvocationRuntime\Execution\InvocationRuntimeResult;

/** Revalidates detached Phase 14D evidence before any proposal projection. */
#[Api(ApiStatus::Internal)]
final class BridgeGuard
{
    public static function eligibleResult(InvocationRuntimeResult $runtimeResult): ModelResult
    {
        $receipt = $runtimeResult->receipt();
        if (InvocationState::Retired !== $runtimeResult->state()) {
            throw ProposalBridgeRejectedException::ineligible('runtime_not_retired', 'runtime');
        }
        if (!$receipt->cleanupComplete()) {
            throw ProposalBridgeRejectedException::ineligible('cleanup_incomplete', 'runtime');
        }
        if (InvocationOutcome::Succeeded !== $receipt->outcome()) {
            throw ProposalBridgeRejectedException::ineligible('outcome_not_succeeded', 'receipt', outcome: $receipt->outcome()->value);
        }
        if (1 !== $receipt->attemptCount()) {
            throw ProposalBridgeRejectedException::ineligible('attempt_count_not_one', 'receipt', attemptCount: $receipt->attemptCount());
        }

        $result = $runtimeResult->result();
        if (null === $result) {
            throw ProposalBridgeRejectedException::ineligible('result_missing', 'result');
        }
        $receiptResultIdentity = $receipt->resultIdentity();
        if (null === $receiptResultIdentity || !$result->identity()->equals($receiptResultIdentity)) {
            throw ProposalBridgeRejectedException::ineligible('receipt_result_mismatch', 'receipt');
        }
        if (!$result->invocationIdentity()->equals($receipt->invocationIdentity())) {
            throw ProposalBridgeRejectedException::ineligible('invocation_mismatch', 'receipt');
        }
        if (!$result->profile()->equals($receipt->profile())) {
            throw ProposalBridgeRejectedException::ineligible('profile_mismatch', 'receipt');
        }
        if (!self::sameUsage($result->usage(), $receipt->usage())) {
            throw ProposalBridgeRejectedException::ineligible('usage_relation_mismatch', 'receipt');
        }
        if (!self::sameCost($result->cost(), $receipt->cost())) {
            throw ProposalBridgeRejectedException::ineligible('cost_relation_mismatch', 'receipt');
        }
        if (ResultMode::ActionSuggestion !== $result->mode()) {
            throw ProposalBridgeRejectedException::ineligible('result_mode_ineligible', 'result', mode: $result->mode()->value);
        }
        if (FinishReason::ActionSuggested !== $result->finishReason()) {
            throw ProposalBridgeRejectedException::ineligible('finish_reason_ineligible', 'result', mode: $result->mode()->value);
        }

        return $result;
    }

    private static function sameUsage(?UsageEvidence $left, ?UsageEvidence $right): bool
    {
        return null === $left ? null === $right : null !== $right && $left->equals($right);
    }

    private static function sameCost(?CostEvidence $left, ?CostEvidence $right): bool
    {
        return null === $left ? null === $right : null !== $right && $left->equals($right);
    }

    private function __construct() {}
}
