<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceApplication\Execution;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceApplication\Exception\ExternalServiceApplicationOperationException;
use Kongono\ExternalServiceApplication\Internal\FailureMapper;
use Kongono\ExternalServiceApplication\Internal\OutcomeMapper;
use Kongono\ExternalServiceContract\Lifecycle\ExternalServiceLifecycleState;
use Kongono\ExternalServiceContract\Outcome\ExternalServiceOperationResult;
use Kongono\ExternalServiceContract\Outcome\ExternalServiceOutcome;
use Kongono\ExternalServiceCredentialRuntime\Execution\CredentialLeaseRuntimeResult;
use Kongono\ExternalServiceCredentialRuntime\Lifecycle\CredentialLeaseRuntimeState;
use Kongono\ExternalServiceCredentialRuntime\Outcome\CredentialLeaseRuntimeOutcome;
use Kongono\NetworkEgressRuntime\Execution\EgressRuntimeResult;

/** Combines exact detached service, credential, and egress terminal evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class ExternalServiceApplicationResult
{
    public function __construct(
        private ExternalServiceOperationResult $operationResult,
        private CredentialLeaseRuntimeResult $credentialResult,
        private EgressRuntimeResult $egressResult,
    ) {
        $egressOperation = $egressResult->operationResult();
        $expectedOutcome = OutcomeMapper::map($egressOperation->receipt()->outcome());
        $expectedFailure = FailureMapper::map($egressOperation->failure());
        $expectedLifecycle = in_array($expectedOutcome, [ExternalServiceOutcome::CancelledAfterPossibleEffect, ExternalServiceOutcome::UncertainEffect], true)
            ? ExternalServiceLifecycleState::Failed
            : ExternalServiceLifecycleState::Retired;

        if (CredentialLeaseRuntimeOutcome::Succeeded !== $credentialResult->outcome()
            || CredentialLeaseRuntimeState::Retired !== $credentialResult->state()
            || null !== $credentialResult->failure()
            || 1 !== $credentialResult->acquisitionAttemptCount()
            || 1 !== $credentialResult->materialAccessAttemptCount()
            || 1 !== $credentialResult->retirementAttemptCount()
            || !$credentialResult->cleanupComplete()
            || $operationResult->operationIdentity()->value() !== $egressOperation->receipt()->operationIdentity()->value()
            || $operationResult->outcome() !== $expectedOutcome
            || $operationResult->lifecycleState() !== $expectedLifecycle
            || (null === $expectedFailure ? null !== $operationResult->failure() : null === $operationResult->failure() || !$expectedFailure->equals($operationResult->failure()))
        ) {
            throw ExternalServiceApplicationOperationException::invariant('result_mapping');
        }
    }

    public function operationResult(): ExternalServiceOperationResult
    {
        return $this->operationResult;
    }

    public function credentialResult(): CredentialLeaseRuntimeResult
    {
        return $this->credentialResult;
    }

    public function egressResult(): EgressRuntimeResult
    {
        return $this->egressResult;
    }
}
