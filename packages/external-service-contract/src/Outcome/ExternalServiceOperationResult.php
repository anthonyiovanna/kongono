<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceContract\Outcome;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceContract\Binding\ServiceBindingIdentity;
use Kongono\ExternalServiceContract\Exception\InvalidExternalServiceContractException;
use Kongono\ExternalServiceContract\Failure\ExternalServiceFailure;
use Kongono\ExternalServiceContract\Lifecycle\ExternalServiceLifecycleState;
use Kongono\ExternalServiceContract\Operation\ExternalServiceOperationIdentity;

/** Carries coherent terminal operation evidence without asserting external truth. */
#[Api(ApiStatus::Experimental)]
final readonly class ExternalServiceOperationResult
{
    public function __construct(
        private ExternalServiceOperationIdentity $operationIdentity,
        private ServiceBindingIdentity $bindingIdentity,
        private ExternalServiceLifecycleState $lifecycleState,
        private ExternalServiceOutcome $outcome,
        private ?ExternalServiceFailure $failure,
    ) {
        if (!in_array($lifecycleState, [ExternalServiceLifecycleState::Retired, ExternalServiceLifecycleState::Failed], true)) {
            throw InvalidExternalServiceContractException::invalidLifecycle('terminal_state_required', $lifecycleState->value);
        }
        if (ExternalServiceOutcome::Succeeded === $outcome) {
            if (ExternalServiceLifecycleState::Retired !== $lifecycleState || null !== $failure) {
                throw InvalidExternalServiceContractException::invalidResult('success_relation', $outcome->value, $lifecycleState->value);
            }

            return;
        }
        if (null === $failure) {
            throw InvalidExternalServiceContractException::invalidResult('failure_required', $outcome->value, $lifecycleState->value);
        }
        if (in_array($outcome, [ExternalServiceOutcome::CancelledAfterPossibleEffect, ExternalServiceOutcome::UncertainEffect], true) && ExternalServiceLifecycleState::Failed !== $lifecycleState) {
            throw InvalidExternalServiceContractException::invalidResult('possible_effect_requires_failed', $outcome->value, $lifecycleState->value);
        }
    }

    public function operationIdentity(): ExternalServiceOperationIdentity
    {
        return $this->operationIdentity;
    }

    public function bindingIdentity(): ServiceBindingIdentity
    {
        return $this->bindingIdentity;
    }

    public function lifecycleState(): ExternalServiceLifecycleState
    {
        return $this->lifecycleState;
    }

    public function outcome(): ExternalServiceOutcome
    {
        return $this->outcome;
    }

    public function failure(): ?ExternalServiceFailure
    {
        return $this->failure;
    }

    public function succeeded(): bool
    {
        return ExternalServiceOutcome::Succeeded === $this->outcome;
    }

    public function equals(self $other): bool
    {
        return $this->operationIdentity->equals($other->operationIdentity)
            && $this->bindingIdentity->equals($other->bindingIdentity)
            && $this->lifecycleState === $other->lifecycleState
            && $this->outcome === $other->outcome
            && (null === $this->failure ? null === $other->failure : null !== $other->failure && $this->failure->equals($other->failure));
    }
}
