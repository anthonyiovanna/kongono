<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationRuntime\Runtime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationBuild\Artifact\InvocationPlanArtifactValidator;
use Kongono\ModelInvocationContract\Lifecycle\InvocationState;
use Kongono\ModelInvocationContract\Receipt\InvocationOutcome;
use Kongono\ModelInvocationContract\Result\ModelResult;
use Kongono\ModelInvocationContract\Stream\ModelStreamEvent;
use Kongono\ModelInvocationRuntime\Adapter\InvocationAdapterEmission;
use Kongono\ModelInvocationRuntime\Adapter\InvocationAdapterSession;
use Kongono\ModelInvocationRuntime\Diagnostic\ModelInvocationRuntimeDiagnostic;
use Kongono\ModelInvocationRuntime\Exception\InvalidInvocationRuntimeException;
use Kongono\ModelInvocationRuntime\Exception\InvocationOperationException;
use Kongono\ModelInvocationRuntime\Exception\InvocationRejectedException;
use Kongono\ModelInvocationRuntime\Execution\InvocationRuntimeRequest;
use Kongono\ModelInvocationRuntime\Execution\InvocationRuntimeResult;
use Kongono\ModelInvocationRuntime\Internal\BudgetMeter;
use Kongono\ModelInvocationRuntime\Internal\LifecycleFailureComposer;
use Kongono\ModelInvocationRuntime\Internal\ReceiptFactory;
use Kongono\ModelInvocationRuntime\Internal\RuntimeGuard;
use Kongono\ModelInvocationRuntime\Internal\StreamGuard;
use Throwable;

/** Runs one exact-current synchronous model invocation with one direct adapter. */
#[Api(ApiStatus::Experimental)]
final class ModelInvocationRuntime
{
    private bool $operationActive = false;

    public function run(InvocationRuntimeRequest $request): InvocationRuntimeResult
    {
        if ($this->operationActive) {
            throw InvalidInvocationRuntimeException::invalid('reentrancy');
        }
        $this->operationActive = true;

        try {
            return $this->runOperation($request);
        } finally {
            $this->operationActive = false;
        }
    }

    private function runOperation(InvocationRuntimeRequest $request): InvocationRuntimeResult
    {
        try {
            (new InvocationPlanArtifactValidator())->validateCurrent($request->source(), $request->artifactBytes());
            RuntimeGuard::validateAdapter($request);
        } catch (Throwable $failure) {
            $receipt = ReceiptFactory::create($request, InvocationOutcome::Rejected, 0, null, true);
            throw new InvocationRejectedException($receipt, $failure);
        }

        try {
            $meter = new BudgetMeter($request->source()->budget(), $this->clock($request));
        } catch (InvocationOperationException $failure) {
            throw $failure;
        } catch (Throwable $failure) {
            throw $this->operationFailure($request, ModelInvocationRuntimeDiagnostic::ProbeFailed, 'clock', 'initial_probe', $failure);
        }

        $session = null;
        $primaryFailure = null;
        $cleanupFailures = [];
        $outcome = null;
        $result = null;
        $events = [];

        try {
            if ($this->cancelled($request)) {
                $outcome = InvocationOutcome::Cancelled;
            } else {
                $this->checkpoint($request, $meter);
                try {
                    $session = $request->adapter()->open($request->source());
                } catch (Throwable $failure) {
                    throw $this->operationFailure($request, ModelInvocationRuntimeDiagnostic::AdapterFailed, 'open', 'adapter_open', $failure);
                }
                [$outcome, $result, $events] = $this->execute($request, $session, $meter);
            }
        } catch (Throwable $failure) {
            $primaryFailure = $failure;
        } finally {
            if ($session instanceof InvocationAdapterSession) {
                try {
                    $session->close();
                } catch (Throwable $failure) {
                    $cleanupFailures[] = $failure;
                }
            }
        }

        try {
            $this->checkpoint($request, $meter);
        } catch (Throwable $failure) {
            if (null === $primaryFailure) {
                $primaryFailure = $failure;
            }
        }

        LifecycleFailureComposer::throwIfNeeded($request, $primaryFailure, $cleanupFailures);

        if (!$outcome instanceof InvocationOutcome) {
            $failure = InvalidInvocationRuntimeException::invalid('operation_incomplete');
            throw $this->operationFailure($request, ModelInvocationRuntimeDiagnostic::ProtocolViolated, 'lifecycle', 'operation_incomplete', $failure);
        }
        $receipt = ReceiptFactory::create($request, $outcome, 1, $result, true);

        return new InvocationRuntimeResult(
            $receipt,
            $result,
            $events,
            InvocationState::Retired,
            $meter->elapsedMilliseconds(),
            $meter->retainedEvidenceBytes(),
        );
    }

    /** @return array{InvocationOutcome, ?ModelResult, list<ModelStreamEvent>} */
    private function execute(InvocationRuntimeRequest $request, InvocationAdapterSession $session, BudgetMeter $meter): array
    {
        return $request->source()->request()->streaming()
            ? $this->executeStreaming($request, $session, $meter)
            : $this->executeNonStreaming($request, $session, $meter);
    }

    /** @return array{InvocationOutcome, ?ModelResult, list<ModelStreamEvent>} */
    private function executeNonStreaming(InvocationRuntimeRequest $request, InvocationAdapterSession $session, BudgetMeter $meter): array
    {
        [$cancelled, $emission] = $this->pull($request, $session, $meter);
        if ($cancelled) {
            return [InvocationOutcome::Cancelled, null, []];
        }
        $result = $this->requireResult($request, $emission);
        $this->validateResult($request, $result, new StreamGuard(), $meter);
        [$cancelled, $trailing] = $this->pull($request, $session, $meter);
        if ($cancelled) {
            return [InvocationOutcome::Cancelled, null, []];
        }
        if (null !== $trailing) {
            throw $this->protocolFailure($request, 'trailing_emission');
        }

        return [InvocationOutcome::Succeeded, $result, []];
    }

    /** @return array{InvocationOutcome, ?ModelResult, list<ModelStreamEvent>} */
    private function executeStreaming(InvocationRuntimeRequest $request, InvocationAdapterSession $session, BudgetMeter $meter): array
    {
        $guard = new StreamGuard();
        $events = [];
        $limits = $request->source()->request()->limits();
        while (!$guard->isTerminal()) {
            [$cancelled, $emission] = $this->pull($request, $session, $meter);
            if ($cancelled) {
                return [InvocationOutcome::Cancelled, null, $events];
            }
            $event = $this->requireEvent($request, $emission);
            $observedCount = count($events) + 1;
            if ($observedCount > $limits->maxStreamEvents()) {
                throw $this->budgetFailure($request, 'stream_events', $limits->maxStreamEvents(), $observedCount);
            }
            try {
                $payloadBytes = $guard->acceptEvent($event, $request->source()->request(), $request->source()->budget());
            } catch (InvalidInvocationRuntimeException $failure) {
                throw $this->classifiedFailure($request, $failure);
            }
            if (!$meter->retain($payloadBytes)) {
                throw $this->budgetFailure($request, 'retained_evidence', $meter->retainedLimit(), $meter->retainedEvidenceBytes());
            }
            $events[] = $event;
        }

        if (!$guard->isCompleted()) {
            if ($this->requireTrailingNull($request, $session, $meter)) {
                return [InvocationOutcome::Cancelled, null, $events];
            }

            return [InvocationOutcome::Failed, null, $events];
        }

        [$cancelled, $emission] = $this->pull($request, $session, $meter);
        if ($cancelled) {
            return [InvocationOutcome::Cancelled, null, $events];
        }
        $result = $this->requireResult($request, $emission);
        $this->validateResult($request, $result, $guard, $meter);
        [$cancelled, $trailing] = $this->pull($request, $session, $meter);
        if ($cancelled) {
            return [InvocationOutcome::Cancelled, null, $events];
        }
        if (null !== $trailing) {
            throw $this->protocolFailure($request, 'trailing_emission');
        }

        return [InvocationOutcome::Succeeded, $result, $events];
    }

    private function validateResult(InvocationRuntimeRequest $request, ModelResult $result, StreamGuard $guard, BudgetMeter $meter): void
    {
        try {
            $outputBytes = RuntimeGuard::validateResult($request, $result);
            $guard->validateFinalEvidence($result, $request->source()->request(), $request->source()->budget());
        } catch (InvalidInvocationRuntimeException $failure) {
            throw $this->classifiedFailure($request, $failure);
        }
        $limit = $request->source()->request()->limits()->maxOutputBytes();
        if ($outputBytes > $limit) {
            throw $this->budgetFailure($request, 'output_bytes', $limit, $outputBytes);
        }
        if (!$meter->retain($outputBytes)) {
            throw $this->budgetFailure($request, 'retained_evidence', $meter->retainedLimit(), $meter->retainedEvidenceBytes());
        }
    }

    /** @return array{bool, ?InvocationAdapterEmission} */
    private function pull(InvocationRuntimeRequest $request, InvocationAdapterSession $session, BudgetMeter $meter): array
    {
        if ($this->cancelled($request)) {
            return [true, null];
        }
        $this->checkpoint($request, $meter);
        try {
            $emission = $session->next();
        } catch (Throwable $failure) {
            throw $this->operationFailure($request, ModelInvocationRuntimeDiagnostic::AdapterFailed, 'pull', 'adapter_pull', $failure);
        }
        if ($this->cancelled($request)) {
            return [true, null];
        }
        $this->checkpoint($request, $meter);

        return [false, $emission];
    }

    private function requireTrailingNull(InvocationRuntimeRequest $request, InvocationAdapterSession $session, BudgetMeter $meter): bool
    {
        [$cancelled, $trailing] = $this->pull($request, $session, $meter);
        if ($cancelled) {
            return true;
        }
        if (null !== $trailing) {
            throw $this->protocolFailure($request, 'trailing_emission');
        }

        return false;
    }

    private function requireResult(InvocationRuntimeRequest $request, ?InvocationAdapterEmission $emission): ModelResult
    {
        if (null === $emission || null === $emission->resultValue() || null !== $emission->eventValue()) {
            throw $this->protocolFailure($request, 'result_required');
        }

        return $emission->resultValue();
    }

    private function requireEvent(InvocationRuntimeRequest $request, ?InvocationAdapterEmission $emission): ModelStreamEvent
    {
        if (null === $emission || null === $emission->eventValue() || null !== $emission->resultValue()) {
            throw $this->protocolFailure($request, 'event_required');
        }

        return $emission->eventValue();
    }

    private function cancelled(InvocationRuntimeRequest $request): bool
    {
        try {
            return $request->cancellation()->isCancellationRequested();
        } catch (Throwable $failure) {
            throw $this->operationFailure($request, ModelInvocationRuntimeDiagnostic::ProbeFailed, 'cancellation', 'probe_failed', $failure);
        }
    }

    private function clock(InvocationRuntimeRequest $request): int
    {
        try {
            return $request->clock()->milliseconds();
        } catch (Throwable $failure) {
            throw $this->operationFailure($request, ModelInvocationRuntimeDiagnostic::ProbeFailed, 'clock', 'probe_failed', $failure);
        }
    }

    private function checkpoint(InvocationRuntimeRequest $request, BudgetMeter $meter): void
    {
        try {
            $meter->observeTime($this->clock($request));
        } catch (InvocationOperationException $failure) {
            throw $failure;
        } catch (InvalidInvocationRuntimeException $failure) {
            throw $this->operationFailure($request, ModelInvocationRuntimeDiagnostic::ProbeFailed, 'clock', $failure->violation(), $failure);
        }
        if ($meter->durationExceeded()) {
            throw $this->budgetFailure(
                $request,
                'duration',
                $request->source()->budget()->maxDurationMilliseconds(),
                $meter->elapsedMilliseconds(),
            );
        }
    }

    private function classifiedFailure(InvocationRuntimeRequest $request, InvalidInvocationRuntimeException $failure): InvocationOperationException
    {
        if (in_array($failure->violation(), ['budget_exceeded', 'cost_protocol'], true)) {
            return $this->operationFailure($request, ModelInvocationRuntimeDiagnostic::BudgetExceeded, 'budget', $failure->violation(), $failure);
        }

        return $this->operationFailure($request, ModelInvocationRuntimeDiagnostic::ProtocolViolated, 'protocol', $failure->violation(), $failure);
    }

    private function protocolFailure(InvocationRuntimeRequest $request, string $violation): InvocationOperationException
    {
        $failure = InvalidInvocationRuntimeException::invalid('stream_protocol');

        return $this->operationFailure($request, ModelInvocationRuntimeDiagnostic::ProtocolViolated, 'protocol', $violation, $failure);
    }

    private function budgetFailure(InvocationRuntimeRequest $request, string $violation, int $limit, int $observedCount): InvocationOperationException
    {
        $failure = InvalidInvocationRuntimeException::invalid('budget_exceeded');

        return $this->operationFailure($request, ModelInvocationRuntimeDiagnostic::BudgetExceeded, 'budget', $violation, $failure, $limit, $observedCount);
    }

    private function operationFailure(
        InvocationRuntimeRequest $request,
        ModelInvocationRuntimeDiagnostic $diagnostic,
        string $stage,
        string $violation,
        Throwable $previous,
        int $limit = 0,
        int $observedCount = 0,
    ): InvocationOperationException {
        return new InvocationOperationException(
            ReceiptFactory::create($request, InvocationOutcome::Failed, 1, null, true),
            $diagnostic,
            $stage,
            $violation,
            $previous,
            $limit,
            $observedCount,
        );
    }
}
