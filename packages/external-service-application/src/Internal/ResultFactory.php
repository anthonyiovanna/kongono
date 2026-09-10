<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceApplication\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceApplication\Execution\ExternalServiceApplicationResult;
use Kongono\ExternalServiceApplication\Execution\ExternalServiceApplicationRunInput;
use Kongono\ExternalServiceApplication\Profile\ExternalServiceApplicationProfile;
use Kongono\ExternalServiceContract\Lifecycle\ExternalServiceLifecycleState;
use Kongono\ExternalServiceContract\Outcome\ExternalServiceOperationResult;
use Kongono\ExternalServiceContract\Outcome\ExternalServiceOutcome;
use Kongono\ExternalServiceCredentialRuntime\Execution\CredentialLeaseRuntimeResult;
use Kongono\NetworkEgressRuntime\Execution\EgressRuntimeResult;

/** Builds detached aggregate evidence from exact successful nested executions. */
#[Api(ApiStatus::Internal)]
final class ResultFactory
{
    public static function create(
        ExternalServiceApplicationProfile $profile,
        ExternalServiceApplicationRunInput $input,
        CredentialLeaseRuntimeResult $credentialResult,
        EgressRuntimeResult $egressResult,
    ): ExternalServiceApplicationResult {
        $egressOperationResult = $egressResult->operationResult();
        $outcome = OutcomeMapper::map($egressOperationResult->receipt()->outcome());
        $lifecycle = in_array($outcome, [ExternalServiceOutcome::CancelledAfterPossibleEffect, ExternalServiceOutcome::UncertainEffect], true)
            ? ExternalServiceLifecycleState::Failed
            : ExternalServiceLifecycleState::Retired;
        $operationResult = new ExternalServiceOperationResult(
            $input->operationIdentity(),
            $profile->binding()->identity(),
            $lifecycle,
            $outcome,
            FailureMapper::map($egressOperationResult->failure()),
        );

        return new ExternalServiceApplicationResult($operationResult, $credentialResult, $egressResult);
    }

    private function __construct() {}
}
