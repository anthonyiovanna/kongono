<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceCredentialRuntime\Runtime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceContract\Credential\CredentialLease;
use Kongono\ExternalServiceCredentialRuntime\Exception\CredentialLeaseOperationException;
use Kongono\ExternalServiceCredentialRuntime\Exception\InvalidCredentialLeaseRuntimeException;
use Kongono\ExternalServiceCredentialRuntime\Execution\CredentialLeaseRuntimeRequest;
use Kongono\ExternalServiceCredentialRuntime\Execution\CredentialLeaseRuntimeResult;
use Kongono\ExternalServiceCredentialRuntime\Failure\CredentialLeaseRuntimeFailure;
use Kongono\ExternalServiceCredentialRuntime\Failure\CredentialLeaseRuntimeFailureKind;
use Kongono\ExternalServiceCredentialRuntime\Internal\CredentialLeaseGuard;
use Kongono\ExternalServiceCredentialRuntime\Internal\LeaseBudgetMeter;
use Kongono\ExternalServiceCredentialRuntime\Internal\LeaseLifecycle;
use Kongono\ExternalServiceCredentialRuntime\Internal\LifecycleFailureComposer;
use Kongono\ExternalServiceCredentialRuntime\Internal\ResultFactory;
use Kongono\ExternalServiceCredentialRuntime\Lifecycle\CredentialLeaseRuntimeState;
use Kongono\ExternalServiceCredentialRuntime\Outcome\CredentialLeaseRuntimeOutcome;
use Throwable;

/** Coordinates one synchronous borrowed-material lease and deterministic retirement. */
#[Api(ApiStatus::Experimental)]
final class CredentialLeaseRuntime
{
    private bool $operationActive = false;

    public function run(CredentialLeaseRuntimeRequest $request): CredentialLeaseRuntimeResult
    {
        if ($this->operationActive) {
            throw InvalidCredentialLeaseRuntimeException::invalid('reentrancy');
        }
        $this->operationActive = true;

        try {
            return $this->runOperation($request);
        } finally {
            $this->operationActive = false;
        }
    }

    private function runOperation(CredentialLeaseRuntimeRequest $request): CredentialLeaseRuntimeResult
    {
        $lifecycle = new LeaseLifecycle();
        try {
            $initialMilliseconds = $request->clock()->milliseconds();
        } catch (Throwable $primary) {
            $this->throwBeforeAcquisition($lifecycle, $primary, new CredentialLeaseRuntimeFailure(CredentialLeaseRuntimeFailureKind::Clock, 'clock_failed'), 'prepared');
        }
        try {
            $meter = new LeaseBudgetMeter($request->leaseRequest()->maximumLifetimeMilliseconds(), $initialMilliseconds);
        } catch (InvalidCredentialLeaseRuntimeException $primary) {
            $this->throwBeforeAcquisition($lifecycle, $primary, new CredentialLeaseRuntimeFailure(CredentialLeaseRuntimeFailureKind::Clock, $primary->violation()), 'prepared');
        }

        $lifecycle->transition(CredentialLeaseRuntimeState::Acquiring);
        $lifecycle->attemptAcquisition();
        try {
            $lease = $request->source()->acquire($request->leaseRequest());
        } catch (Throwable $primary) {
            $lifecycle->transition(CredentialLeaseRuntimeState::Failed);
            $result = ResultFactory::create(
                $lifecycle,
                CredentialLeaseRuntimeOutcome::FailedBeforeAcquisition,
                new CredentialLeaseRuntimeFailure(CredentialLeaseRuntimeFailureKind::Source, 'source_failed'),
                $meter->elapsedMilliseconds(),
                true,
            );
            throw new CredentialLeaseOperationException($result, $primary, 'acquiring');
        }

        $lifecycle->transition(CredentialLeaseRuntimeState::Acquired);
        $primary = null;
        $primaryEvidence = null;
        $primaryStage = 'acquired';
        $limit = 0;
        $observedCount = 0;

        try {
            CredentialLeaseGuard::validateInitial($request->leaseRequest(), $lease);
        } catch (Throwable $failure) {
            $primary = $failure;
            $primaryEvidence = $this->leaseProtocolEvidence($failure);
        }

        if (null === $primary) {
            [$primary, $primaryEvidence, $limit, $observedCount] = $this->observe($request, $meter);
        }

        if (null === $primary) {
            $lifecycle->transition(CredentialLeaseRuntimeState::Using);
            $lifecycle->attemptMaterialAccess();
            try {
                $lease->withMaterial($request->consumer());
            } catch (Throwable $failure) {
                $primary = $failure;
                $primaryEvidence = new CredentialLeaseRuntimeFailure(CredentialLeaseRuntimeFailureKind::Consumer, 'consumer_failed');
                $primaryStage = 'using';
            }
        }

        if (null === $primary) {
            try {
                CredentialLeaseGuard::validateAfterMaterial($lease);
            } catch (Throwable $failure) {
                $primary = $failure;
                $primaryEvidence = $this->leaseProtocolEvidence($failure);
                $primaryStage = 'using';
            }
        }

        if (null === $primary) {
            [$primary, $primaryEvidence, $limit, $observedCount] = $this->observe($request, $meter);
            $primaryStage = 'using';
        }

        [$cleanupFailure, $cleanupReason] = $this->retire($lease, $lifecycle);

        if (null === $primary && null === $cleanupFailure) {
            [$primary, $primaryEvidence, $limit, $observedCount] = $this->observe($request, $meter);
            $primaryStage = 'retired';
        }

        $cleanupResult = null;
        if (null !== $cleanupFailure) {
            $cleanupResult = ResultFactory::create(
                $lifecycle,
                CredentialLeaseRuntimeOutcome::RetirementUncertain,
                new CredentialLeaseRuntimeFailure(CredentialLeaseRuntimeFailureKind::Retirement, $cleanupReason),
                $meter->elapsedMilliseconds(),
                false,
            );
        }

        $primaryResult = null;
        if (null !== $primary && null !== $primaryEvidence && null === $cleanupFailure) {
            $primaryResult = ResultFactory::create(
                $lifecycle,
                0 === $lifecycle->materialAccessAttemptCount()
                    ? CredentialLeaseRuntimeOutcome::FailedBeforeMaterial
                    : CredentialLeaseRuntimeOutcome::MaterialUseUncertain,
                $primaryEvidence,
                $meter->elapsedMilliseconds(),
                true,
            );
        }

        LifecycleFailureComposer::throwIfNeeded(
            $primary,
            $primaryResult,
            $primaryStage,
            $limit,
            $observedCount,
            $cleanupFailure,
            $cleanupResult,
        );

        return ResultFactory::create(
            $lifecycle,
            CredentialLeaseRuntimeOutcome::Succeeded,
            null,
            $meter->elapsedMilliseconds(),
            true,
        );
    }

    /** @return array{?Throwable, ?CredentialLeaseRuntimeFailure, int, int} */
    private function observe(CredentialLeaseRuntimeRequest $request, LeaseBudgetMeter $meter): array
    {
        try {
            $milliseconds = $request->clock()->milliseconds();
        } catch (Throwable $failure) {
            return [$failure, new CredentialLeaseRuntimeFailure(CredentialLeaseRuntimeFailureKind::Clock, 'clock_failed'), 0, 0];
        }
        try {
            $meter->observe($milliseconds);
        } catch (InvalidCredentialLeaseRuntimeException $failure) {
            $kind = 'lifetime_exceeded' === $failure->violation()
                ? CredentialLeaseRuntimeFailureKind::Lifetime
                : CredentialLeaseRuntimeFailureKind::Clock;

            return [
                $failure,
                new CredentialLeaseRuntimeFailure($kind, $failure->violation()),
                CredentialLeaseRuntimeFailureKind::Lifetime === $kind ? $meter->maximumLifetimeMilliseconds() : 0,
                CredentialLeaseRuntimeFailureKind::Lifetime === $kind ? $meter->elapsedMilliseconds() : 0,
            ];
        }

        return [null, null, 0, 0];
    }

    /** @return array{?Throwable, string} */
    private function retire(CredentialLease $lease, LeaseLifecycle $lifecycle): array
    {
        $lifecycle->transition(CredentialLeaseRuntimeState::Retiring);
        $lifecycle->attemptRetirement();
        try {
            $lease->retire();
        } catch (Throwable $failure) {
            $lifecycle->transition(CredentialLeaseRuntimeState::Uncertain);

            return [$failure, 'retirement_failed'];
        }
        try {
            CredentialLeaseGuard::validateRetired($lease);
        } catch (Throwable $failure) {
            $lifecycle->transition(CredentialLeaseRuntimeState::Uncertain);

            return [$failure, $failure instanceof InvalidCredentialLeaseRuntimeException ? 'retirement_state_invalid' : 'retirement_failed'];
        }
        $lifecycle->transition(CredentialLeaseRuntimeState::Retired);

        return [null, 'retirement_failed'];
    }

    private function leaseProtocolEvidence(Throwable $failure): CredentialLeaseRuntimeFailure
    {
        $reason = $failure instanceof InvalidCredentialLeaseRuntimeException
            && in_array($failure->violation(), ['lease_reference_mismatch', 'lease_purpose_mismatch', 'lease_state_invalid'], true)
            ? $failure->violation()
            : 'lease_state_invalid';

        return new CredentialLeaseRuntimeFailure(CredentialLeaseRuntimeFailureKind::LeaseProtocol, $reason);
    }

    private function throwBeforeAcquisition(
        LeaseLifecycle $lifecycle,
        Throwable $primary,
        CredentialLeaseRuntimeFailure $failure,
        string $stage,
    ): never {
        $lifecycle->transition(CredentialLeaseRuntimeState::Failed);
        $result = ResultFactory::create($lifecycle, CredentialLeaseRuntimeOutcome::FailedBeforeAcquisition, $failure, 0, true);
        throw new CredentialLeaseOperationException($result, $primary, $stage);
    }
}
