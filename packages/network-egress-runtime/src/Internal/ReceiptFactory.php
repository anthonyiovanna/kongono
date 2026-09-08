<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressRuntime\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressContract\Cancellation\CancellationEvidence;
use Kongono\NetworkEgressContract\Cancellation\CancellationState;
use Kongono\NetworkEgressContract\Capability\TransportCapabilitySet;
use Kongono\NetworkEgressContract\Failure\EgressFailure;
use Kongono\NetworkEgressContract\Lifecycle\EgressLifecycleState;
use Kongono\NetworkEgressContract\Outcome\EgressOperationResult;
use Kongono\NetworkEgressContract\Outcome\EgressOutcome;
use Kongono\NetworkEgressContract\Receipt\EgressReceipt;
use Kongono\NetworkEgressContract\Transfer\EgressTransferEvidence;
use Kongono\NetworkEgressContract\Transport\NegotiatedProtocol;
use Kongono\NetworkEgressRuntime\Execution\EgressRuntimeRequest;
use Kongono\NetworkEgressRuntime\Execution\EgressRuntimeResult;
use Psr\Http\Message\ResponseInterface;

/** Constructs terminal runtime evidence from explicit bounded observations. */
#[Api(ApiStatus::Internal)]
final class ReceiptFactory
{
    public static function create(
        EgressRuntimeRequest $request,
        EgressOutcome $outcome,
        int $attemptCount,
        EgressTransferEvidence $transfer,
        CancellationState $cancellation,
        int $observation,
        NegotiatedProtocol $protocol,
        TransportCapabilitySet $capabilities,
        ?ResponseInterface $response,
        ?EgressFailure $failure,
        int $elapsed,
        int $candidates,
        int $progress,
        int $peakBuffered,
        int $retained,
    ): EgressRuntimeResult {
        $lifecycle = match ($outcome) {
            EgressOutcome::Succeeded => EgressLifecycleState::Succeeded,
            EgressOutcome::CancelledBeforeEffect, EgressOutcome::CancelledAfterPossibleEffect => EgressLifecycleState::Cancelled,
            EgressOutcome::UncertainEffect => EgressLifecycleState::Uncertain,
            EgressOutcome::RejectedBeforeAttempt, EgressOutcome::FailedBeforeEffect => EgressLifecycleState::Failed,
        };
        $receipt = new EgressReceipt(
            $request->receiptIdentity(),
            $request->operation()->identity(),
            0 === $attemptCount ? null : $request->attemptIdentity(),
            $lifecycle,
            $outcome,
            $attemptCount,
            $transfer,
            new CancellationEvidence($cancellation, $observation),
            $protocol,
            $capabilities,
        );

        return new EgressRuntimeResult(
            new EgressOperationResult($receipt, $response, $failure),
            $elapsed,
            $candidates,
            $progress,
            $peakBuffered,
            $retained,
        );
    }

    private function __construct() {}
}
