<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressContract\Receipt;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressContract\Cancellation\CancellationEvidence;
use Kongono\NetworkEgressContract\Cancellation\CancellationState;
use Kongono\NetworkEgressContract\Capability\TransportCapabilitySet;
use Kongono\NetworkEgressContract\Exception\InvalidNetworkEgressContractException;
use Kongono\NetworkEgressContract\Identity\EgressAttemptIdentity;
use Kongono\NetworkEgressContract\Identity\EgressOperationIdentity;
use Kongono\NetworkEgressContract\Identity\EgressReceiptIdentity;
use Kongono\NetworkEgressContract\Lifecycle\EgressLifecycleState;
use Kongono\NetworkEgressContract\Outcome\EgressOutcome;
use Kongono\NetworkEgressContract\Transfer\EgressTransferEvidence;
use Kongono\NetworkEgressContract\Transfer\EgressTransferState;
use Kongono\NetworkEgressContract\Transport\NegotiatedProtocol;

/** Stores coherent terminal evidence without proving a remote effect or retry authority. */
#[Api(ApiStatus::Experimental)]
final readonly class EgressReceipt
{
    public function __construct(
        private EgressReceiptIdentity $receiptIdentity,
        private EgressOperationIdentity $operationIdentity,
        private ?EgressAttemptIdentity $attemptIdentity,
        private EgressLifecycleState $finalLifecycleState,
        private EgressOutcome $outcome,
        private int $connectionAttemptCount,
        private EgressTransferEvidence $transfer,
        private CancellationEvidence $cancellation,
        private NegotiatedProtocol $negotiatedProtocol,
        private TransportCapabilitySet $capabilities,
    ) {
        if ($connectionAttemptCount < 0 || $connectionAttemptCount > 16) {
            throw InvalidNetworkEgressContractException::invalidOutcomeOrReceipt('attempt_count_range', $outcome->value);
        }
        $valid = match ($outcome) {
            EgressOutcome::RejectedBeforeAttempt => $this->beforeAttempt(
                EgressLifecycleState::Failed,
                CancellationState::NotRequested,
            ),
            EgressOutcome::FailedBeforeEffect => $this->beforeEffect(
                EgressLifecycleState::Failed,
                CancellationState::NotRequested,
            ),
            EgressOutcome::CancelledBeforeEffect => $this->beforeEffect(
                EgressLifecycleState::Cancelled,
                CancellationState::RequestedBeforeAttempt,
            ),
            EgressOutcome::Succeeded => $this->succeeded(),
            EgressOutcome::CancelledAfterPossibleEffect => $this->afterPossibleEffect(
                EgressLifecycleState::Cancelled,
                true,
            ),
            EgressOutcome::UncertainEffect => $this->afterPossibleEffect(
                EgressLifecycleState::Uncertain,
                false,
            ),
        };
        if (!$valid) {
            $violation = match ($outcome) {
                EgressOutcome::RejectedBeforeAttempt, EgressOutcome::FailedBeforeEffect, EgressOutcome::CancelledBeforeEffect => 'before_attempt_relation',
                EgressOutcome::Succeeded => 'success_relation',
                EgressOutcome::CancelledAfterPossibleEffect => 'cancelled_after_effect_relation',
                EgressOutcome::UncertainEffect => 'uncertain_effect_relation',
            };
            throw InvalidNetworkEgressContractException::invalidOutcomeOrReceipt($violation, $outcome->value, $transfer->state()->value);
        }
    }

    public function receiptIdentity(): EgressReceiptIdentity
    {
        return $this->receiptIdentity;
    }

    public function operationIdentity(): EgressOperationIdentity
    {
        return $this->operationIdentity;
    }

    public function attemptIdentity(): ?EgressAttemptIdentity
    {
        return $this->attemptIdentity;
    }

    public function finalLifecycleState(): EgressLifecycleState
    {
        return $this->finalLifecycleState;
    }

    public function outcome(): EgressOutcome
    {
        return $this->outcome;
    }

    public function connectionAttemptCount(): int
    {
        return $this->connectionAttemptCount;
    }

    public function transfer(): EgressTransferEvidence
    {
        return $this->transfer;
    }

    public function cancellation(): CancellationEvidence
    {
        return $this->cancellation;
    }

    public function negotiatedProtocol(): NegotiatedProtocol
    {
        return $this->negotiatedProtocol;
    }

    public function capabilities(): TransportCapabilitySet
    {
        return $this->capabilities;
    }

    private function beforeAttempt(EgressLifecycleState $lifecycle, CancellationState $cancellation): bool
    {
        return 0 === $this->connectionAttemptCount
            && null === $this->attemptIdentity
            && $lifecycle === $this->finalLifecycleState
            && EgressTransferState::None === $this->transfer->state()
            && NegotiatedProtocol::Unknown === $this->negotiatedProtocol
            && $cancellation === $this->cancellation->state();
    }

    private function beforeEffect(EgressLifecycleState $lifecycle, CancellationState $cancellation): bool
    {
        $attemptRelation = (0 === $this->connectionAttemptCount && null === $this->attemptIdentity)
            || ($this->connectionAttemptCount > 0 && null !== $this->attemptIdentity);

        return $attemptRelation
            && $lifecycle === $this->finalLifecycleState
            && EgressTransferState::None === $this->transfer->state()
            && $cancellation === $this->cancellation->state();
    }

    private function succeeded(): bool
    {
        return $this->connectionAttemptCount > 0
            && null !== $this->attemptIdentity
            && EgressLifecycleState::Succeeded === $this->finalLifecycleState
            && EgressTransferState::ResponseReceived === $this->transfer->state()
            && CancellationState::NotRequested === $this->cancellation->state();
    }

    private function afterPossibleEffect(EgressLifecycleState $lifecycle, bool $cancellationRequired): bool
    {
        $possibleEffect = EgressTransferState::None !== $this->transfer->state();
        $cancellationValid = $cancellationRequired
            ? CancellationState::RequestedAfterPossibleEffect === $this->cancellation->state()
            : CancellationState::RequestedBeforeAttempt !== $this->cancellation->state();

        return $this->connectionAttemptCount > 0
            && null !== $this->attemptIdentity
            && $lifecycle === $this->finalLifecycleState
            && $possibleEffect
            && $cancellationValid;
    }
}
