<?php

declare(strict_types=1);

namespace Kongono\AgentRuntime\Runtime;

use Kongono\AgentPrincipalContract\Proposal\ActionProposal;
use Kongono\AgentPrincipalContract\Receipt\AgentReceipt;
use Kongono\AgentPrincipalContract\Receipt\ReceiptOutcome;
use Kongono\AgentRuntime\Budget\AgentRuntimeUsage;
use Kongono\AgentRuntime\Capability\AgentCapabilityInvocation;
use Kongono\AgentRuntime\Capability\AgentCapabilityOutcome;
use Kongono\AgentRuntime\Capability\AgentCapabilityResult;
use Kongono\AgentRuntime\Exception\AgentRuntimeException;
use Kongono\AgentRuntime\Exception\AgentRuntimeOperationException;
use Kongono\AgentRuntime\Internal\BudgetMeter;
use Kongono\AgentRuntime\Internal\LifecycleFailureComposer;
use Kongono\AgentRuntime\Internal\ReceiptFactory;
use Kongono\AgentRuntime\Internal\RuntimeGuard;
use Kongono\AgentRuntime\Proposal\AgentProposalRequest;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Runs one synchronous proposal and at most one exact capability effect. */
#[Api(ApiStatus::Experimental)]
final class AgentRuntime
{
    public function run(AgentRuntimeRequest $request): AgentRuntimeResult
    {
        $result = null;
        $primaryFailure = null;
        $stage = 'proposal';

        try {
            RuntimeGuard::validateBinding($request->declaration(), $request->authorityPlan(), $request->budget());
            $meter = new BudgetMeter($request->budget(), self::clock($request));
            $result = $this->execute($request, $meter, $stage);
        } catch (Throwable $failure) {
            $primaryFailure = $failure instanceof AgentRuntimeException
                ? $failure
                : AgentRuntimeOperationException::failed($stage, $failure);
        }

        $cleanupFailures = self::closePorts($request);
        LifecycleFailureComposer::throwIfNeeded($primaryFailure, $cleanupFailures);

        if (!$result instanceof AgentRuntimeResult) {
            throw AgentRuntimeOperationException::failed($stage);
        }

        return $result;
    }

    private function execute(AgentRuntimeRequest $request, BudgetMeter $meter, string &$stage): AgentRuntimeResult
    {
        self::checkpoint($request, $meter);
        if (self::cancelled($request)) {
            return self::result(AgentRuntimeOutcome::Cancelled, $meter->usage());
        }
        if (!$meter->canBeginProposal()) {
            return self::result(AgentRuntimeOutcome::BudgetExceeded, $meter->usage());
        }
        if (!self::current($request)) {
            return self::result(AgentRuntimeOutcome::AuthorityDenied, $meter->usage());
        }

        $proposalRequest = new AgentProposalRequest(
            $request->declaration()->principal(),
            $request->operation(),
            $request->intent(),
            RuntimeGuard::permittedCapabilities($request->authorityPlan()),
            $request->declaration()->constraints(),
            $request->declaration()->evidenceReferences(),
        );
        $stage = 'proposal';
        try {
            $proposalResult = $request->proposalPort()->propose($proposalRequest);
        } catch (Throwable $failure) {
            throw AgentRuntimeOperationException::failed('proposal', $failure);
        }
        $meter->recordProposal($proposalResult->tokenUsage(), $proposalResult->outputByteUsage());
        $proposal = $proposalResult->proposal();
        $capability = RuntimeGuard::validateProposal($proposal, $request->declaration(), $request->authorityPlan(), $proposalRequest);
        $portIndex = RuntimeGuard::portIndex($capability, $request->capabilityIdentities());
        $receipts = [self::receipt($proposal, ReceiptOutcome::Proposed, $proposalResult->evidenceSha256(), $meter->usage())];

        self::checkpoint($request, $meter);
        if (self::cancelled($request)) {
            $receipts[] = self::receipt($proposal, ReceiptOutcome::Cancelled, $proposalResult->evidenceSha256(), $meter->usage());
            return self::retired(AgentRuntimeOutcome::Cancelled, $meter->usage(), $proposal, null, $receipts, $proposalResult->evidenceSha256());
        }
        if ($meter->exceeded()) {
            return self::retired(AgentRuntimeOutcome::BudgetExceeded, $meter->usage(), $proposal, null, $receipts, $proposalResult->evidenceSha256());
        }

        self::checkpoint($request, $meter);
        if (self::cancelled($request)) {
            $receipts[] = self::receipt($proposal, ReceiptOutcome::Cancelled, $proposalResult->evidenceSha256(), $meter->usage());
            return self::retired(AgentRuntimeOutcome::Cancelled, $meter->usage(), $proposal, null, $receipts, $proposalResult->evidenceSha256());
        }
        if (!$meter->canBeginCapability()) {
            return self::retired(AgentRuntimeOutcome::BudgetExceeded, $meter->usage(), $proposal, null, $receipts, $proposalResult->evidenceSha256());
        }
        if (!self::current($request)) {
            return self::retired(AgentRuntimeOutcome::AuthorityDenied, $meter->usage(), $proposal, null, $receipts, $proposalResult->evidenceSha256());
        }

        $receipts[] = self::receipt($proposal, ReceiptOutcome::Authorized, $proposalResult->evidenceSha256(), $meter->usage());
        $receipts[] = self::receipt($proposal, ReceiptOutcome::Attempted, $proposalResult->evidenceSha256(), $meter->usage());
        $invocation = new AgentCapabilityInvocation($proposal, $request->operation(), $request->intent(), $capability, $request->budget(), $meter->usage());
        $stage = 'capability';
        $ports = $request->capabilityPorts();
        try {
            $capabilityResult = $ports[$portIndex]->invoke($invocation);
        } catch (Throwable $failure) {
            throw AgentRuntimeOperationException::failed('capability', $failure);
        }
        $meter->recordCapability(strlen($capabilityResult->outputBytes()));
        $receiptOutcome = AgentCapabilityOutcome::Performed === $capabilityResult->outcome() ? ReceiptOutcome::Performed : ReceiptOutcome::Failed;
        $receipts[] = self::receipt($proposal, $receiptOutcome, $capabilityResult->evidenceSha256(), $meter->usage());

        self::checkpoint($request, $meter);
        if (self::cancelled($request)) {
            $receipts[] = self::receipt($proposal, ReceiptOutcome::Cancelled, $capabilityResult->evidenceSha256(), $meter->usage());
            return self::retired(AgentRuntimeOutcome::Cancelled, $meter->usage(), $proposal, $capabilityResult, $receipts, $capabilityResult->evidenceSha256());
        }
        if ($meter->exceeded()) {
            return self::retired(AgentRuntimeOutcome::BudgetExceeded, $meter->usage(), $proposal, $capabilityResult, $receipts, $capabilityResult->evidenceSha256());
        }

        $outcome = AgentCapabilityOutcome::Performed === $capabilityResult->outcome()
            ? AgentRuntimeOutcome::Performed
            : AgentRuntimeOutcome::CapabilityFailed;
        return self::retired($outcome, $meter->usage(), $proposal, $capabilityResult, $receipts, $capabilityResult->evidenceSha256());
    }

    private static function clock(AgentRuntimeRequest $request): int
    {
        try {
            return $request->clock()->milliseconds();
        } catch (Throwable $failure) {
            throw AgentRuntimeOperationException::failed('clock', $failure);
        }
    }

    private static function checkpoint(AgentRuntimeRequest $request, BudgetMeter $meter): void
    {
        $meter->checkpoint(self::clock($request));
    }

    private static function cancelled(AgentRuntimeRequest $request): bool
    {
        try {
            return $request->cancellation()->isCancellationRequested();
        } catch (Throwable $failure) {
            throw AgentRuntimeOperationException::failed('cancellation', $failure);
        }
    }

    private static function current(AgentRuntimeRequest $request): bool
    {
        try {
            return $request->authorityCurrentness()->isCurrent($request->authorityPlan(), $request->operation());
        } catch (Throwable $failure) {
            throw AgentRuntimeOperationException::failed('authority', $failure);
        }
    }

    /** @return list<Throwable> */
    private static function closePorts(AgentRuntimeRequest $request): array
    {
        $failures = [];
        $ports = $request->capabilityPorts();
        for ($index = count($ports) - 1; $index >= 0; --$index) {
            try {
                $ports[$index]->close();
            } catch (Throwable $failure) {
                $failures[] = $failure;
            }
        }
        try {
            $request->proposalPort()->close();
        } catch (Throwable $failure) {
            $failures[] = $failure;
        }
        return $failures;
    }

    private static function receipt(ActionProposal $proposal, ReceiptOutcome $outcome, string $evidence, AgentRuntimeUsage $usage): AgentReceipt
    {
        return ReceiptFactory::create($proposal, $outcome, $evidence, $usage);
    }

    /** @param list<AgentReceipt> $receipts */
    private static function retired(
        AgentRuntimeOutcome $outcome,
        AgentRuntimeUsage $usage,
        ActionProposal $proposal,
        ?AgentCapabilityResult $capabilityResult,
        array $receipts,
        string $evidence,
    ): AgentRuntimeResult {
        $receipts[] = self::receipt($proposal, ReceiptOutcome::Retired, $evidence, $usage);
        return self::result($outcome, $usage, $proposal, $capabilityResult, $receipts);
    }

    /** @param list<AgentReceipt> $receipts */
    private static function result(
        AgentRuntimeOutcome $outcome,
        AgentRuntimeUsage $usage,
        ?ActionProposal $proposal = null,
        ?AgentCapabilityResult $capabilityResult = null,
        array $receipts = [],
    ): AgentRuntimeResult {
        return new AgentRuntimeResult($outcome, AgentRuntimeState::Retired, $usage, $proposal, $capabilityResult, $receipts, true);
    }
}
