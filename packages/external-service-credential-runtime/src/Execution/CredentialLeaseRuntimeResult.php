<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceCredentialRuntime\Execution;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceCredentialRuntime\Exception\InvalidCredentialLeaseRuntimeException;
use Kongono\ExternalServiceCredentialRuntime\Failure\CredentialLeaseRuntimeFailure;
use Kongono\ExternalServiceCredentialRuntime\Failure\CredentialLeaseRuntimeFailureKind;
use Kongono\ExternalServiceCredentialRuntime\Lifecycle\CredentialLeaseRuntimeState;
use Kongono\ExternalServiceCredentialRuntime\Outcome\CredentialLeaseRuntimeOutcome;

/** Returns detached bounded terminal evidence after the lease lifecycle ends. */
#[Api(ApiStatus::Experimental)]
final readonly class CredentialLeaseRuntimeResult
{
    public function __construct(
        private CredentialLeaseRuntimeOutcome $outcome,
        private CredentialLeaseRuntimeState $state,
        private ?CredentialLeaseRuntimeFailure $failure,
        private int $elapsedMilliseconds,
        private int $acquisitionAttemptCount,
        private int $materialAccessAttemptCount,
        private int $retirementAttemptCount,
        private bool $cleanupComplete,
    ) {
        if ($elapsedMilliseconds < 0 || !$this->binary($acquisitionAttemptCount) || !$this->binary($materialAccessAttemptCount) || !$this->binary($retirementAttemptCount)) {
            throw InvalidCredentialLeaseRuntimeException::invalid('result_count_range');
        }
        if (!$this->validRelation()) {
            throw InvalidCredentialLeaseRuntimeException::invalid('terminal_result_relation');
        }
    }

    public function outcome(): CredentialLeaseRuntimeOutcome
    {
        return $this->outcome;
    }

    public function state(): CredentialLeaseRuntimeState
    {
        return $this->state;
    }

    public function failure(): ?CredentialLeaseRuntimeFailure
    {
        return $this->failure;
    }

    public function elapsedMilliseconds(): int
    {
        return $this->elapsedMilliseconds;
    }

    public function acquisitionAttemptCount(): int
    {
        return $this->acquisitionAttemptCount;
    }

    public function materialAccessAttemptCount(): int
    {
        return $this->materialAccessAttemptCount;
    }

    public function retirementAttemptCount(): int
    {
        return $this->retirementAttemptCount;
    }

    public function cleanupComplete(): bool
    {
        return $this->cleanupComplete;
    }

    private function binary(int $count): bool
    {
        return 0 === $count || 1 === $count;
    }

    private function validRelation(): bool
    {
        return match ($this->outcome) {
            CredentialLeaseRuntimeOutcome::Succeeded => CredentialLeaseRuntimeState::Retired === $this->state
                && null === $this->failure && 1 === $this->acquisitionAttemptCount && 1 === $this->materialAccessAttemptCount
                && 1 === $this->retirementAttemptCount && $this->cleanupComplete,
            CredentialLeaseRuntimeOutcome::FailedBeforeAcquisition => CredentialLeaseRuntimeState::Failed === $this->state
                && null !== $this->failure
                && ((0 === $this->acquisitionAttemptCount && CredentialLeaseRuntimeFailureKind::Clock === $this->failure->kind())
                    || (1 === $this->acquisitionAttemptCount && CredentialLeaseRuntimeFailureKind::Source === $this->failure->kind()))
                && 0 === $this->materialAccessAttemptCount && 0 === $this->retirementAttemptCount && $this->cleanupComplete,
            CredentialLeaseRuntimeOutcome::FailedBeforeMaterial => CredentialLeaseRuntimeState::Retired === $this->state
                && null !== $this->failure
                && in_array($this->failure->kind(), [CredentialLeaseRuntimeFailureKind::Clock, CredentialLeaseRuntimeFailureKind::LeaseProtocol, CredentialLeaseRuntimeFailureKind::Lifetime], true)
                && 1 === $this->acquisitionAttemptCount && 0 === $this->materialAccessAttemptCount
                && 1 === $this->retirementAttemptCount && $this->cleanupComplete,
            CredentialLeaseRuntimeOutcome::MaterialUseUncertain => CredentialLeaseRuntimeState::Retired === $this->state
                && null !== $this->failure
                && in_array($this->failure->kind(), [CredentialLeaseRuntimeFailureKind::Clock, CredentialLeaseRuntimeFailureKind::LeaseProtocol, CredentialLeaseRuntimeFailureKind::Lifetime, CredentialLeaseRuntimeFailureKind::Consumer], true)
                && 1 === $this->acquisitionAttemptCount && 1 === $this->materialAccessAttemptCount
                && 1 === $this->retirementAttemptCount && $this->cleanupComplete,
            CredentialLeaseRuntimeOutcome::RetirementUncertain => CredentialLeaseRuntimeState::Uncertain === $this->state
                && null !== $this->failure && CredentialLeaseRuntimeFailureKind::Retirement === $this->failure->kind()
                && 1 === $this->acquisitionAttemptCount && in_array($this->materialAccessAttemptCount, [0, 1], true)
                && 1 === $this->retirementAttemptCount && !$this->cleanupComplete,
        };
    }
}
