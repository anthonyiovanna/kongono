<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressRuntime\Runtime;

use Error;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressContract\Cancellation\CancellationState;
use Kongono\NetworkEgressContract\Capability\TransportCapabilitySet;
use Kongono\NetworkEgressContract\Failure\EgressFailure;
use Kongono\NetworkEgressContract\Failure\EgressFailureKind;
use Kongono\NetworkEgressContract\Outcome\EgressOutcome;
use Kongono\NetworkEgressContract\Transfer\EgressTransferEvidence;
use Kongono\NetworkEgressContract\Transfer\EgressTransferState;
use Kongono\NetworkEgressContract\Transport\NegotiatedProtocol;
use Kongono\NetworkEgressPolicyBuild\Exception\ExceptionInterface as PolicyException;
use Kongono\NetworkEgressRuntime\Diagnostic\NetworkEgressRuntimeDiagnostic;
use Kongono\NetworkEgressRuntime\Exception\EgressOperationException;
use Kongono\NetworkEgressRuntime\Exception\InvalidEgressRuntimeException;
use Kongono\NetworkEgressRuntime\Execution\EgressRuntimeRequest;
use Kongono\NetworkEgressRuntime\Execution\EgressRuntimeResult;
use Kongono\NetworkEgressRuntime\Internal\BudgetMeter;
use Kongono\NetworkEgressRuntime\Internal\HeaderMeter;
use Kongono\NetworkEgressRuntime\Internal\LifecycleFailureComposer;
use Kongono\NetworkEgressRuntime\Internal\ReceiptFactory;
use Kongono\NetworkEgressRuntime\Internal\RuntimeGuard;
use Kongono\NetworkEgressRuntime\Internal\TransferGuard;
use Kongono\NetworkEgressRuntime\Internal\TransportGuard;
use Kongono\NetworkEgressRuntime\Transport\EgressTransportEmission;
use Kongono\NetworkEgressRuntime\Transport\EgressTransportRequest;
use Kongono\NetworkEgressRuntime\Transport\EgressTransportSession;
use Throwable;

/** Coordinates one bounded synchronous egress attempt through explicit ports. */
#[Api(ApiStatus::Experimental)]
final class NetworkEgressRuntime
{
    private bool $active = false;

    public function run(EgressRuntimeRequest $request): EgressRuntimeResult
    {
        if ($this->active) {
            throw InvalidEgressRuntimeException::invalid('reentrancy');
        }
        $this->active = true;
        try {
            return $this->runOperation($request);
        } finally {
            $this->active = false;
        }
    }

    private function runOperation(EgressRuntimeRequest $request): EgressRuntimeResult
    {
        $transfer = new EgressTransferEvidence(EgressTransferState::None, 0, 0);
        $protocol = NegotiatedProtocol::Unknown;
        $capabilities = $request->policySource()->requiredCapabilities();
        $attempts = 0;
        $candidates = 0;
        $observations = 0;
        $progress = 0;
        $peakBuffered = 0;
        $retained = 0;
        $session = null;
        $terminal = null;
        $primary = null;
        $meter = null;
        $cleanupFailures = [];

        try {
            $meter = new BudgetMeter($this->clock($request, null, $transfer, $capabilities, $attempts, $observations));
            if ($this->cancelled($request, $meter, $transfer, $capabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained)) {
                $terminal = $this->cancelledResult($request, $meter, $transfer, $capabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained, $protocol);
            } else {
                $this->checkpoint($request, $meter, $transfer, $capabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained);
                try {
                    RuntimeGuard::validatePolicy($request);
                } catch (InvalidEgressRuntimeException|PolicyException $failure) {
                    $terminal = $this->failureResult($request, EgressOutcome::RejectedBeforeAttempt, EgressFailureKind::PolicyDenied, 'policy_rejected', $meter, $transfer, $capabilities, 0, $observations, 0, 0, 0, 0, NegotiatedProtocol::Unknown);
                }
                if (null === $terminal) {
                    $terminal = $this->admitAndExecute($request, $meter, $transfer, $capabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained, $protocol, $session);
                }
            }
        } catch (Error $failure) {
            $primary = $failure;
        } catch (Throwable $failure) {
            if ($failure instanceof InvalidEgressRuntimeException && 'total_timeout' === $failure->violation() && $meter instanceof BudgetMeter) {
                $terminal = $this->failureResult($request, $this->failureOutcome($transfer), EgressFailureKind::Timeout, 'total_timeout', $meter, $transfer, $capabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained, $protocol);
            } else {
                $primary = $failure instanceof EgressOperationException
                    ? $failure
                    : $this->operationFailure($request, NetworkEgressRuntimeDiagnostic::TransportFailed, 'lifecycle', 'operation_incomplete', $failure, null, $transfer, $capabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained, $protocol);
            }
        } finally {
            if ($session instanceof EgressTransportSession) {
                try {
                    $session->close();
                } catch (Throwable $failure) {
                    $cleanupFailures[] = $failure;
                }
            }
        }

        $cleanupResult = $this->cleanupResult($request, $terminal, $transfer, $capabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained, $protocol);
        LifecycleFailureComposer::throwIfNeeded($primary, $cleanupFailures, $cleanupResult);
        if (!$terminal instanceof EgressRuntimeResult) {
            throw InvalidEgressRuntimeException::invalid('operation_incomplete');
        }

        return $terminal;
    }

    private function admitAndExecute(
        EgressRuntimeRequest $request,
        BudgetMeter $meter,
        EgressTransferEvidence &$transfer,
        TransportCapabilitySet &$capabilities,
        int &$attempts,
        int &$observations,
        int &$candidates,
        int &$progress,
        int &$peakBuffered,
        int &$retained,
        NegotiatedProtocol &$protocol,
        ?EgressTransportSession &$session,
    ): EgressRuntimeResult {
        if (!$this->authorityCurrent($request, $meter, $transfer, $capabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained)) {
            return $this->failureResult($request, EgressOutcome::RejectedBeforeAttempt, EgressFailureKind::PolicyDenied, 'authority_rejected', $meter, $transfer, $capabilities, 0, $observations, 0, 0, 0, 0, NegotiatedProtocol::Unknown);
        }
        if ($this->cancelled($request, $meter, $transfer, $capabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained)) {
            return $this->cancelledResult($request, $meter, $transfer, $capabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained, $protocol);
        }
        try {
            $resolution = $request->resolver()->resolve($request->operation()->origin());
        } catch (Error $failure) {
            throw $failure;
        } catch (Throwable $failure) {
            throw $this->operationFailure($request, NetworkEgressRuntimeDiagnostic::ResolutionFailed, 'resolution', 'resolver_failed', $failure, $meter, $transfer, $capabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained, $protocol);
        }
        if ($this->cancelled($request, $meter, $transfer, $capabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained)) {
            return $this->cancelledResult($request, $meter, $transfer, $capabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained, $protocol);
        }
        if (!$resolution->succeeded()) {
            return $this->failureResult($request, EgressOutcome::FailedBeforeEffect, EgressFailureKind::Resolution, 'resolution_failed', $meter, $transfer, $capabilities, 0, $observations, 0, 0, 0, 0, NegotiatedProtocol::Unknown);
        }
        $evidence = $resolution->evidence();
        if (null === $evidence) {
            throw InvalidEgressRuntimeException::invalid('operation_incomplete');
        }
        $candidates = count($evidence->addresses());
        try {
            RuntimeGuard::validateResolution($request, $evidence);
        } catch (InvalidEgressRuntimeException $failure) {
            return $this->failureResult($request, EgressOutcome::FailedBeforeEffect, EgressFailureKind::PolicyDenied, 'address_denied', $meter, $transfer, $capabilities, 0, $observations, $candidates, 0, 0, 0, NegotiatedProtocol::Unknown);
        }
        if (!$this->authorityCurrent($request, $meter, $transfer, $capabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained)) {
            return $this->failureResult($request, EgressOutcome::RejectedBeforeAttempt, EgressFailureKind::PolicyDenied, 'authority_rejected', $meter, $transfer, $capabilities, 0, $observations, $candidates, 0, 0, 0, NegotiatedProtocol::Unknown);
        }
        if ($this->cancelled($request, $meter, $transfer, $capabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained)) {
            return $this->cancelledResult($request, $meter, $transfer, $capabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained, $protocol);
        }
        $firstCapabilities = $this->transportCapabilities($request, $meter, $transfer, $capabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained);
        if ($this->cancelled($request, $meter, $transfer, $firstCapabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained)) {
            return $this->cancelledResult($request, $meter, $transfer, $firstCapabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained, $protocol);
        }
        $secondCapabilities = $this->transportCapabilities($request, $meter, $transfer, $firstCapabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained);
        if ($this->cancelled($request, $meter, $transfer, $secondCapabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained)) {
            return $this->cancelledResult($request, $meter, $transfer, $secondCapabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained, $protocol);
        }
        try {
            TransportGuard::validate($request, $firstCapabilities, $secondCapabilities);
        } catch (InvalidEgressRuntimeException $failure) {
            return $this->failureResult($request, EgressOutcome::FailedBeforeEffect, EgressFailureKind::Connection, 'transport_denied', $meter, $transfer, $firstCapabilities, 0, $observations, $candidates, 0, 0, 0, NegotiatedProtocol::Unknown);
        }
        $capabilities = $firstCapabilities;
        if ($this->cancelled($request, $meter, $transfer, $capabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained)) {
            return $this->cancelledResult($request, $meter, $transfer, $capabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained, $protocol);
        }
        $connectStarted = $this->checkpoint($request, $meter, $transfer, $capabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained);
        $attempts = 1;
        try {
            $session = $request->transport()->open(new EgressTransportRequest($request->operation(), $evidence->selectedAddress(), $request->attemptIdentity()));
        } catch (Error $failure) {
            throw $failure;
        } catch (Throwable $failure) {
            throw $this->operationFailure($request, NetworkEgressRuntimeDiagnostic::TransportFailed, 'open', 'transport_open', $failure, $meter, $transfer, $capabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained, $protocol);
        }
        $connectEnded = $this->checkpoint($request, $meter, $transfer, $capabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained);
        if ($connectEnded - $connectStarted > $request->operation()->budget()->connectTimeoutMilliseconds()) {
            return $this->failureResult($request, EgressOutcome::FailedBeforeEffect, EgressFailureKind::Timeout, 'connect_timeout', $meter, $transfer, $capabilities, 1, $observations, $candidates, 0, 0, 0, $protocol);
        }
        if ($this->cancelled($request, $meter, $transfer, $capabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained)) {
            return $this->cancelledResult($request, $meter, $transfer, $capabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained, $protocol);
        }

        return $this->pullToTerminal($request, $session, $meter, $transfer, $capabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained, $protocol, $connectEnded);
    }

    private function pullToTerminal(
        EgressRuntimeRequest $request,
        EgressTransportSession $session,
        BudgetMeter $meter,
        EgressTransferEvidence &$transfer,
        TransportCapabilitySet $capabilities,
        int $attempts,
        int &$observations,
        int $candidates,
        int &$progress,
        int &$peakBuffered,
        int &$retained,
        NegotiatedProtocol &$protocol,
        int $firstByteStarted,
    ): EgressRuntimeResult {
        $guard = new TransferGuard($request->operation()->budget(), $capabilities);
        while (true) {
            if ($this->cancelled($request, $meter, $transfer, $capabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained)) {
                return $this->cancelledResult($request, $meter, $transfer, $capabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained, $protocol);
            }
            try {
                $emission = $session->next();
            } catch (Error $failure) {
                throw $failure;
            } catch (Throwable $failure) {
                throw $this->operationFailure($request, NetworkEgressRuntimeDiagnostic::TransportFailed, 'pull', 'transport_pull', $failure, $meter, $transfer, $capabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained, $protocol);
            }
            if (null === $emission) {
                return $this->failureResult($request, $this->failureOutcome($transfer), EgressFailureKind::Protocol, 'premature_end', $meter, $transfer, $capabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained, $protocol);
            }
            $failure = $this->acceptEmission($guard, $emission);
            $transfer = $guard->transfer();
            $protocol = $guard->protocol();
            $progress = $guard->progressCount();
            $peakBuffered = $guard->peakBufferedBytes();
            $retained = $guard->retainedEvidenceBytes();
            $now = $this->checkpoint($request, $meter, $transfer, $capabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained);
            if (null === $failure && 0 === $transfer->responseBytes() && $now - $firstByteStarted > $request->operation()->budget()->firstByteTimeoutMilliseconds()) {
                $failure = new EgressFailure(EgressFailureKind::Timeout, 'first_byte_timeout');
            }
            if (null !== $failure) {
                return $this->failureResult($request, $this->failureOutcome($transfer), $failure->kind(), $failure->reason(), $meter, $transfer, $capabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained, $protocol);
            }
            if ($this->cancelled($request, $meter, $transfer, $capabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained)) {
                return $this->cancelledResult($request, $meter, $transfer, $capabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained, $protocol);
            }
            if (!$emission->terminal()) {
                continue;
            }
            if (null !== $emission->response()) {
                try {
                    HeaderMeter::enforce($emission->response(), $request->operation()->budget());
                } catch (InvalidEgressRuntimeException $failure) {
                    return $this->failureResult($request, EgressOutcome::UncertainEffect, EgressFailureKind::Budget, 'header_budget', $meter, $transfer, $capabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained, $protocol);
                }
            }
            if ($this->cancelled($request, $meter, $transfer, $capabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained)) {
                return $this->cancelledResult($request, $meter, $transfer, $capabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained, $protocol);
            }
            try {
                $trailing = $session->next();
            } catch (Error $throwable) {
                throw $throwable;
            } catch (Throwable $throwable) {
                throw $this->operationFailure($request, NetworkEgressRuntimeDiagnostic::TransportFailed, 'pull', 'transport_pull', $throwable, $meter, $transfer, $capabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained, $protocol);
            }
            if (null !== $trailing) {
                return $this->failureResult($request, $this->failureOutcome($transfer), EgressFailureKind::Protocol, 'trailing_emission', $meter, $transfer, $capabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained, $protocol);
            }
            if (null !== $emission->failure()) {
                return $this->failureResult($request, $this->failureOutcome($transfer), $emission->failure()->kind(), $emission->failure()->reason(), $meter, $transfer, $capabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained, $protocol);
            }
            $response = $emission->response();
            if (null === $response) {
                throw InvalidEgressRuntimeException::invalid('operation_incomplete');
            }

            return ReceiptFactory::create($request, EgressOutcome::Succeeded, 1, $transfer, CancellationState::NotRequested, $observations, $protocol, $capabilities, $response, null, $meter->elapsedMilliseconds(), $candidates, $progress, $peakBuffered, $retained);
        }
    }

    private function acceptEmission(TransferGuard $guard, EgressTransportEmission $emission): ?EgressFailure
    {
        try {
            $guard->accept($emission);
        } catch (InvalidEgressRuntimeException $failure) {
            $budgetViolations = ['request_budget', 'response_budget', 'buffer_budget', 'evidence_budget'];
            $kind = in_array($failure->violation(), $budgetViolations, true) ? EgressFailureKind::Budget : EgressFailureKind::Protocol;

            return new EgressFailure($kind, in_array($failure->violation(), $budgetViolations, true) ? 'budget_exceeded' : 'protocol_violated');
        }
        return null;
    }

    private function authorityCurrent(EgressRuntimeRequest $request, BudgetMeter $meter, EgressTransferEvidence $transfer, TransportCapabilitySet $capabilities, int $attempts, int &$observations, int $candidates, int $progress, int $peakBuffered, int $retained): bool
    {
        try {
            $current = $request->authorityCurrentness()->isCurrent($request->operation()->authorityReference(), $request->operation()->origin());
        } catch (Error $failure) {
            throw $failure;
        } catch (Throwable $failure) {
            throw $this->operationFailure($request, NetworkEgressRuntimeDiagnostic::AuthorityRejected, 'authority', 'authority_probe', $failure, $meter, $transfer, $capabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained, NegotiatedProtocol::Unknown);
        }
        $this->checkpoint($request, $meter, $transfer, $capabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained);

        return $current;
    }

    private function transportCapabilities(EgressRuntimeRequest $request, BudgetMeter $meter, EgressTransferEvidence $transfer, TransportCapabilitySet $capabilities, int $attempts, int &$observations, int $candidates, int $progress, int $peakBuffered, int $retained): TransportCapabilitySet
    {
        try {
            $observed = $request->transport()->capabilities();
        } catch (Error $failure) {
            throw $failure;
        } catch (Throwable $failure) {
            throw $this->operationFailure($request, NetworkEgressRuntimeDiagnostic::TransportFailed, 'transport', 'transport_capabilities', $failure, $meter, $transfer, $capabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained, NegotiatedProtocol::Unknown);
        }
        $this->checkpoint($request, $meter, $transfer, $observed, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained);

        return $observed;
    }

    /** @phpstan-impure */
    private function cancelled(EgressRuntimeRequest $request, BudgetMeter $meter, EgressTransferEvidence $transfer, TransportCapabilitySet $capabilities, int $attempts, int &$observations, int $candidates, int $progress, int $peakBuffered, int $retained): bool
    {
        ++$observations;
        try {
            return $request->cancellation()->isCancellationRequested();
        } catch (Error $failure) {
            throw $failure;
        } catch (Throwable $failure) {
            throw $this->operationFailure($request, NetworkEgressRuntimeDiagnostic::CancellationObserved, 'cancellation', 'cancellation_probe', $failure, $meter, $transfer, $capabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained, NegotiatedProtocol::Unknown);
        }
    }

    private function checkpoint(EgressRuntimeRequest $request, BudgetMeter $meter, EgressTransferEvidence $transfer, TransportCapabilitySet $capabilities, int $attempts, int $observations, int $candidates, int $progress, int $peakBuffered, int $retained): int
    {
        $now = $this->clock($request, $meter, $transfer, $capabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained);
        try {
            $elapsed = $meter->observe($now);
        } catch (InvalidEgressRuntimeException $failure) {
            throw $this->operationFailure($request, NetworkEgressRuntimeDiagnostic::BudgetExceeded, 'clock', $failure->violation(), $failure, $meter, $transfer, $capabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained, NegotiatedProtocol::Unknown);
        }
        if ($elapsed > $request->operation()->budget()->totalTimeoutMilliseconds()) {
            throw InvalidEgressRuntimeException::invalid('total_timeout');
        }

        return $now;
    }

    private function clock(EgressRuntimeRequest $request, ?BudgetMeter $meter, EgressTransferEvidence $transfer, TransportCapabilitySet $capabilities, int $attempts, int $observations, int $candidates = 0, int $progress = 0, int $peakBuffered = 0, int $retained = 0): int
    {
        try {
            return $request->clock()->milliseconds();
        } catch (Error $failure) {
            throw $failure;
        } catch (Throwable $failure) {
            throw $this->operationFailure($request, NetworkEgressRuntimeDiagnostic::BudgetExceeded, 'clock', 'clock_probe', $failure, $meter, $transfer, $capabilities, $attempts, $observations, $candidates, $progress, $peakBuffered, $retained, NegotiatedProtocol::Unknown);
        }
    }

    private function failureResult(EgressRuntimeRequest $request, EgressOutcome $outcome, EgressFailureKind $kind, string $reason, BudgetMeter $meter, EgressTransferEvidence $transfer, TransportCapabilitySet $capabilities, int $attempts, int $observations, int $candidates, int $progress, int $peakBuffered, int $retained, NegotiatedProtocol $protocol): EgressRuntimeResult
    {
        return ReceiptFactory::create($request, $outcome, $attempts, $transfer, CancellationState::NotRequested, $observations, $protocol, $capabilities, null, new EgressFailure($kind, $reason), $meter->elapsedMilliseconds(), $candidates, $progress, $peakBuffered, $retained);
    }

    private function cancelledResult(EgressRuntimeRequest $request, BudgetMeter $meter, EgressTransferEvidence $transfer, TransportCapabilitySet $capabilities, int $attempts, int $observations, int $candidates, int $progress, int $peakBuffered, int $retained, NegotiatedProtocol $protocol): EgressRuntimeResult
    {
        $possible = $transfer->requestBytes() > 0;
        $outcome = $possible ? EgressOutcome::CancelledAfterPossibleEffect : EgressOutcome::CancelledBeforeEffect;
        $state = $possible ? CancellationState::RequestedAfterPossibleEffect : CancellationState::RequestedBeforeAttempt;

        return ReceiptFactory::create($request, $outcome, $attempts, $transfer, $state, $observations, $protocol, $capabilities, null, new EgressFailure(EgressFailureKind::Cancellation, 'cancellation_observed'), $meter->elapsedMilliseconds(), $candidates, $progress, $peakBuffered, $retained);
    }

    private function cleanupResult(EgressRuntimeRequest $request, ?EgressRuntimeResult $terminal, EgressTransferEvidence $transfer, TransportCapabilitySet $capabilities, int $attempts, int $observations, int $candidates, int $progress, int $peakBuffered, int $retained, NegotiatedProtocol $protocol): EgressRuntimeResult
    {
        $elapsed = $terminal?->elapsedMilliseconds() ?? 0;
        $outcome = $transfer->requestBytes() > 0 ? EgressOutcome::UncertainEffect : EgressOutcome::FailedBeforeEffect;

        return ReceiptFactory::create($request, $outcome, $attempts, $transfer, CancellationState::NotRequested, $observations, $protocol, $capabilities, null, new EgressFailure(EgressFailureKind::Cleanup, 'cleanup_failed'), $elapsed, $candidates, $progress, $peakBuffered, $retained);
    }

    private function operationFailure(EgressRuntimeRequest $request, NetworkEgressRuntimeDiagnostic $diagnostic, string $stage, string $violation, Throwable $previous, ?BudgetMeter $meter, EgressTransferEvidence $transfer, TransportCapabilitySet $capabilities, int $attempts, int $observations, int $candidates, int $progress, int $peakBuffered, int $retained, NegotiatedProtocol $protocol): EgressOperationException
    {
        $outcome = $this->failureOutcome($transfer);
        $kind = EgressOutcome::UncertainEffect === $outcome ? EgressFailureKind::UncertainEffect : EgressFailureKind::Connection;
        $result = ReceiptFactory::create($request, $outcome, $attempts, $transfer, CancellationState::NotRequested, $observations, $protocol, $capabilities, null, new EgressFailure($kind, 'operation_failed'), $meter?->elapsedMilliseconds() ?? 0, $candidates, $progress, $peakBuffered, $retained);

        return new EgressOperationException($result, $diagnostic, ['stage' => $stage, 'violation' => $violation], $previous);
    }

    private function failureOutcome(EgressTransferEvidence $transfer): EgressOutcome
    {
        return $transfer->requestBytes() > 0 ? EgressOutcome::UncertainEffect : EgressOutcome::FailedBeforeEffect;
    }
}
