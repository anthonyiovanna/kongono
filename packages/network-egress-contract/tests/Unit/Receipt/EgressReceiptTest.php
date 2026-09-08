<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressContract\Tests\Unit\Receipt;

use Kongono\NetworkEgressContract\Cancellation\CancellationEvidence;
use Kongono\NetworkEgressContract\Cancellation\CancellationState;
use Kongono\NetworkEgressContract\Capability\TransportCapability;
use Kongono\NetworkEgressContract\Capability\TransportCapabilitySet;
use Kongono\NetworkEgressContract\Exception\InvalidNetworkEgressContractException;
use Kongono\NetworkEgressContract\Identity\EgressAttemptIdentity;
use Kongono\NetworkEgressContract\Identity\EgressOperationIdentity;
use Kongono\NetworkEgressContract\Identity\EgressReceiptIdentity;
use Kongono\NetworkEgressContract\Lifecycle\EgressLifecycleState;
use Kongono\NetworkEgressContract\Outcome\EgressOutcome;
use Kongono\NetworkEgressContract\Receipt\EgressReceipt;
use Kongono\NetworkEgressContract\Transfer\EgressTransferEvidence;
use Kongono\NetworkEgressContract\Transfer\EgressTransferState;
use Kongono\NetworkEgressContract\Transport\NegotiatedProtocol;
use PHPUnit\Framework\TestCase;

final class EgressReceiptTest extends TestCase
{
    public function testEveryOutcomeHasOneCoherentTerminalShape(): void
    {
        $cases = [
            [EgressOutcome::RejectedBeforeAttempt, null, EgressLifecycleState::Failed, 0, EgressTransferState::None, 0, 0, CancellationState::NotRequested, NegotiatedProtocol::Unknown],
            [EgressOutcome::FailedBeforeEffect, null, EgressLifecycleState::Failed, 0, EgressTransferState::None, 0, 0, CancellationState::NotRequested, NegotiatedProtocol::Unknown],
            [EgressOutcome::FailedBeforeEffect, new EgressAttemptIdentity('attempt'), EgressLifecycleState::Failed, 1, EgressTransferState::None, 0, 0, CancellationState::NotRequested, NegotiatedProtocol::Http3],
            [EgressOutcome::CancelledBeforeEffect, null, EgressLifecycleState::Cancelled, 0, EgressTransferState::None, 0, 0, CancellationState::RequestedBeforeAttempt, NegotiatedProtocol::Unknown],
            [EgressOutcome::CancelledBeforeEffect, new EgressAttemptIdentity('attempt'), EgressLifecycleState::Cancelled, 16, EgressTransferState::None, 0, 0, CancellationState::RequestedBeforeAttempt, NegotiatedProtocol::Http2],
            [EgressOutcome::Succeeded, new EgressAttemptIdentity('attempt'), EgressLifecycleState::Succeeded, 1, EgressTransferState::ResponseReceived, 4, 8, CancellationState::NotRequested, NegotiatedProtocol::Http2],
            [EgressOutcome::CancelledAfterPossibleEffect, new EgressAttemptIdentity('attempt'), EgressLifecycleState::Cancelled, 1, EgressTransferState::RequestPartiallySent, 1, 0, CancellationState::RequestedAfterPossibleEffect, NegotiatedProtocol::Unknown],
            [EgressOutcome::UncertainEffect, new EgressAttemptIdentity('attempt'), EgressLifecycleState::Uncertain, 16, EgressTransferState::RequestSent, 2, 0, CancellationState::NotRequested, NegotiatedProtocol::Http11],
        ];
        foreach ($cases as [$outcome, $attempt, $lifecycle, $attempts, $transferState, $requestBytes, $responseBytes, $cancellation, $protocol]) {
            $receipt = new EgressReceipt(
                new EgressReceiptIdentity('receipt'),
                new EgressOperationIdentity('operation'),
                $attempt,
                $lifecycle,
                $outcome,
                $attempts,
                new EgressTransferEvidence($transferState, $requestBytes, $responseBytes),
                new CancellationEvidence($cancellation, 0),
                $protocol,
                new TransportCapabilitySet([TransportCapability::Http11]),
            );
            self::assertSame($outcome, $receipt->outcome());
            self::assertSame($attempts, $receipt->connectionAttemptCount());
            self::assertSame($attempt, $receipt->attemptIdentity());
            self::assertSame($lifecycle, $receipt->finalLifecycleState());
            self::assertSame($protocol, $receipt->negotiatedProtocol());
            self::assertSame(1, $receipt->capabilities()->count());
            self::assertSame('receipt', $receipt->receiptIdentity()->value());
            self::assertSame('operation', $receipt->operationIdentity()->value());
            self::assertSame($transferState, $receipt->transfer()->state());
            self::assertSame($cancellation, $receipt->cancellation()->state());
        }
    }

    public function testContradictoryRelationsAndAttemptBoundsFailClosed(): void
    {
        foreach ([-1, 17] as $attempts) {
            try {
                new EgressReceipt(
                    new EgressReceiptIdentity('receipt'),
                    new EgressOperationIdentity('operation'),
                    null,
                    EgressLifecycleState::Failed,
                    EgressOutcome::FailedBeforeEffect,
                    $attempts,
                    new EgressTransferEvidence(EgressTransferState::None, 0, 0),
                    new CancellationEvidence(CancellationState::NotRequested, 0),
                    NegotiatedProtocol::Unknown,
                    new TransportCapabilitySet([TransportCapability::Http11]),
                );
                self::fail('Expected receipt rejection.');
            } catch (InvalidNetworkEgressContractException $exception) {
                self::assertSame('KGN-EGRESS-CONTRACT-1507', $exception->diagnosticId());
            }
        }

        $this->expectInvalidReceipt(EgressOutcome::RejectedBeforeAttempt, new EgressAttemptIdentity('attempt'), EgressLifecycleState::Failed, 0, EgressTransferState::None, CancellationState::NotRequested, NegotiatedProtocol::Unknown);
        $this->expectInvalidReceipt(EgressOutcome::FailedBeforeEffect, new EgressAttemptIdentity('attempt'), EgressLifecycleState::Failed, 0, EgressTransferState::None, CancellationState::NotRequested, NegotiatedProtocol::Http11);
        $this->expectInvalidReceipt(EgressOutcome::FailedBeforeEffect, null, EgressLifecycleState::Failed, 1, EgressTransferState::None, CancellationState::NotRequested, NegotiatedProtocol::Unknown);
        $this->expectInvalidReceipt(EgressOutcome::CancelledBeforeEffect, null, EgressLifecycleState::Cancelled, 0, EgressTransferState::None, CancellationState::NotRequested, NegotiatedProtocol::Unknown);
        $this->expectInvalidReceipt(EgressOutcome::CancelledBeforeEffect, new EgressAttemptIdentity('attempt'), EgressLifecycleState::Cancelled, 0, EgressTransferState::None, CancellationState::RequestedBeforeAttempt, NegotiatedProtocol::Http11);
        $this->expectInvalidReceipt(EgressOutcome::CancelledBeforeEffect, null, EgressLifecycleState::Cancelled, 1, EgressTransferState::None, CancellationState::RequestedBeforeAttempt, NegotiatedProtocol::Http11);
        $this->expectInvalidReceipt(EgressOutcome::Succeeded, null, EgressLifecycleState::Succeeded, 1, EgressTransferState::ResponseReceived, CancellationState::NotRequested, NegotiatedProtocol::Http11);
        $this->expectInvalidReceipt(EgressOutcome::Succeeded, new EgressAttemptIdentity('attempt'), EgressLifecycleState::Succeeded, 1, EgressTransferState::RequestSent, CancellationState::NotRequested, NegotiatedProtocol::Http11);
        $this->expectInvalidReceipt(EgressOutcome::Succeeded, new EgressAttemptIdentity('attempt'), EgressLifecycleState::Succeeded, 1, EgressTransferState::ResponseReceived, CancellationState::RequestedAfterPossibleEffect, NegotiatedProtocol::Http11);
        $this->expectInvalidReceipt(EgressOutcome::CancelledAfterPossibleEffect, new EgressAttemptIdentity('attempt'), EgressLifecycleState::Cancelled, 1, EgressTransferState::None, CancellationState::RequestedAfterPossibleEffect, NegotiatedProtocol::Unknown);
        $this->expectInvalidReceipt(EgressOutcome::UncertainEffect, new EgressAttemptIdentity('attempt'), EgressLifecycleState::Uncertain, 0, EgressTransferState::RequestPartiallySent, CancellationState::NotRequested, NegotiatedProtocol::Unknown);
    }

    private function expectInvalidReceipt(
        EgressOutcome $outcome,
        ?EgressAttemptIdentity $attemptIdentity,
        EgressLifecycleState $lifecycle,
        int $attemptCount,
        EgressTransferState $transferState,
        CancellationState $cancellationState,
        NegotiatedProtocol $protocol,
    ): void {
        $requestBytes = EgressTransferState::None === $transferState ? 0 : 1;
        $responseBytes = in_array($transferState, [EgressTransferState::ResponsePartiallyReceived, EgressTransferState::ResponseReceived], true) ? 1 : 0;
        try {
            new EgressReceipt(
                new EgressReceiptIdentity('receipt'),
                new EgressOperationIdentity('operation'),
                $attemptIdentity,
                $lifecycle,
                $outcome,
                $attemptCount,
                new EgressTransferEvidence($transferState, $requestBytes, $responseBytes),
                new CancellationEvidence($cancellationState, 0),
                $protocol,
                new TransportCapabilitySet([TransportCapability::Http11]),
            );
            self::fail('Expected contradictory receipt rejection.');
        } catch (InvalidNetworkEgressContractException $exception) {
            self::assertSame('KGN-EGRESS-CONTRACT-1507', $exception->diagnosticId());
        }
    }
}
