<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceInspection\Inspection;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceApplication\Profile\ExternalServiceApplicationProfile;
use Kongono\ExternalServiceApplication\Profile\ExternalServiceApplicationProfileFactory;
use Kongono\ExternalServiceContract\Failure\ExternalServiceFailureKind;
use Kongono\ExternalServiceContract\Lifecycle\ExternalServiceLifecycleState;
use Kongono\ExternalServiceContract\Outcome\ExternalServiceOutcome;
use Kongono\ExternalServiceCredentialRuntime\Lifecycle\CredentialLeaseRuntimeState;
use Kongono\ExternalServiceCredentialRuntime\Outcome\CredentialLeaseRuntimeOutcome;
use Kongono\ExternalServiceInspection\Exception\ExternalServiceInspectionBindingException;
use Kongono\ExternalServiceInspection\Exception\ExternalServiceResultEvidenceException;
use Kongono\NetworkEgressApplication\Profile\NetworkEgressApplicationProfileFactory;
use Kongono\NetworkEgressContract\Budget\EgressBudget;
use Kongono\NetworkEgressContract\Cancellation\CancellationState;
use Kongono\NetworkEgressContract\Capability\TransportCapability;
use Kongono\NetworkEgressContract\Failure\EgressFailureKind;
use Kongono\NetworkEgressContract\Lifecycle\EgressLifecycleState;
use Kongono\NetworkEgressContract\Outcome\EgressOutcome;
use Kongono\NetworkEgressContract\Receipt\EgressReceipt;
use Kongono\NetworkEgressContract\Transfer\EgressTransferState;
use Kongono\NetworkEgressContract\Transport\NegotiatedProtocol;
use Kongono\NetworkEgressPolicyBuild\Policy\EgressPolicySource;
use Throwable;

/** Revalidates exact supplied evidence without loading code, replaying work, or reading messages. */
#[Api(ApiStatus::Experimental)]
final readonly class ExternalServiceInspector
{
    public function inspect(ExternalServiceInspectionRequest $request): ExternalServiceInspection
    {
        $supplied = $request->profile();
        try {
            $reconstructed = (new ExternalServiceApplicationProfileFactory())->create(
                $supplied->bindingSource(),
                $supplied->bindingArtifact()->canonicalBytes(),
                $supplied->binding()->identity(),
                $supplied->networkEgressProfile(),
            );
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw ExternalServiceInspectionBindingException::mismatch('profile_binding', $failure);
        }
        $this->compareProfile($reconstructed, $supplied);

        $binding = $supplied->binding();
        $bindingArtifact = $supplied->bindingArtifact();
        $networkProfile = $supplied->networkEgressProfile();
        $policy = $networkProfile->source();
        $policyArtifact = $networkProfile->artifact();
        $bindingProjection = $bindingArtifact->artifactSha256() . "\n" . $binding->identity()->value();
        $protected = [
            'application_profile_sha256' => $supplied->identity()->value(),
            'binding_source_identity_sha256' => hash('sha256', $supplied->bindingSource()->identity()->value()),
            'binding_artifact_source_sha256' => $bindingArtifact->sourceSha256(),
            'binding_artifact_sha256' => $bindingArtifact->artifactSha256(),
            'selected_binding_sha256' => hash('sha256', $binding->identity()->value()),
            'service_profile_sha256' => hash('sha256', $binding->profile()->value()),
            'endpoint_sha256' => hash('sha256', "endpoint\n" . $bindingProjection),
            'credential_source_sha256' => hash('sha256', $binding->credentialReference()->source()->value()),
            'credential_reference_sha256' => hash('sha256', "credential-reference\n" . $bindingProjection),
            'required_capabilities_sha256' => $this->capabilitySha256($binding->requiredCapabilities()->all()),
            'network_profile_sha256' => $networkProfile->identity()->value(),
            'network_policy_identity_sha256' => hash('sha256', $policy->identity()->value()),
            'network_source_sha256' => $policyArtifact->sourceSha256(),
            'network_artifact_sha256' => $policyArtifact->artifactSha256(),
            'operation_identity_sha256' => '',
            'receipt_identity_sha256' => '',
        ];

        $serviceOutcome = $serviceLifecycle = $serviceFailureKind = null;
        $credentialOutcome = $credentialState = null;
        $egressOutcome = $egressLifecycle = $transferState = $cancellationState = $protocol = $egressFailureKind = null;
        $counts = [];
        $attemptPresent = $responsePresent = $failurePresent = $credentialCleanup = $possibleEffect = false;
        $result = $request->result();
        if (null !== $result) {
            $operation = $result->operationResult();
            $credential = $result->credentialResult();
            $runtime = $result->egressResult();
            $egress = $runtime->operationResult();
            $receipt = $egress->receipt();
            $egressFailure = $egress->failure();
            $serviceFailure = $operation->failure();
            $expectedOutcome = $this->mapOutcome($receipt->outcome());
            $expectedLifecycle = in_array($expectedOutcome, [ExternalServiceOutcome::CancelledAfterPossibleEffect, ExternalServiceOutcome::UncertainEffect], true)
                ? ExternalServiceLifecycleState::Failed
                : ExternalServiceLifecycleState::Retired;
            $expectedFailureKind = $this->mapFailureKind($egressFailure?->kind());
            if (!$operation->bindingIdentity()->equals($binding->identity())
                || $operation->operationIdentity()->value() !== $receipt->operationIdentity()->value()
                || $operation->outcome() !== $expectedOutcome
                || $operation->lifecycleState() !== $expectedLifecycle
                || $operation->failure()?->kind() !== $expectedFailureKind
                || (null === $expectedFailureKind) !== (null === $serviceFailure)
                || CredentialLeaseRuntimeOutcome::Succeeded !== $credential->outcome()
                || CredentialLeaseRuntimeState::Retired !== $credential->state()
                || null !== $credential->failure()
                || 1 !== $credential->acquisitionAttemptCount()
                || 1 !== $credential->materialAccessAttemptCount()
                || 1 !== $credential->retirementAttemptCount()
                || !$credential->cleanupComplete()) {
                throw ExternalServiceResultEvidenceException::mismatch('result_binding');
            }
            if (!$this->coherentReceipt($receipt)
                || !$this->coherentRuntime($runtime, $policy)
                || (EgressOutcome::Succeeded === $receipt->outcome()) !== (null !== $egress->response())
                || (EgressOutcome::Succeeded === $receipt->outcome()) === (null !== $egressFailure)) {
                throw ExternalServiceResultEvidenceException::mismatch('receipt_result');
            }

            $protected['operation_identity_sha256'] = hash('sha256', $operation->operationIdentity()->value());
            $protected['receipt_identity_sha256'] = hash('sha256', $receipt->receiptIdentity()->value());
            $serviceOutcome = $operation->outcome();
            $serviceLifecycle = $operation->lifecycleState();
            $serviceFailureKind = $serviceFailure?->kind();
            $credentialOutcome = $credential->outcome();
            $credentialState = $credential->state();
            $egressOutcome = $receipt->outcome();
            $egressLifecycle = $receipt->finalLifecycleState();
            $transferState = $receipt->transfer()->state();
            $cancellationState = $receipt->cancellation()->state();
            $protocol = $receipt->negotiatedProtocol();
            $egressFailureKind = $egressFailure?->kind();
            $counts = [
                'credential_elapsed_milliseconds' => $credential->elapsedMilliseconds(),
                'credential_acquisition_attempts' => $credential->acquisitionAttemptCount(),
                'credential_material_access_attempts' => $credential->materialAccessAttemptCount(),
                'credential_retirement_attempts' => $credential->retirementAttemptCount(),
                'connection_attempts' => $receipt->connectionAttemptCount(),
                'request_bytes' => $receipt->transfer()->requestBytes(),
                'response_bytes' => $receipt->transfer()->responseBytes(),
                'cancellation_observation_sequence' => $receipt->cancellation()->observationSequence(),
                'egress_elapsed_milliseconds' => $runtime->elapsedMilliseconds(),
                'resolution_candidates' => $runtime->resolutionCandidateCount(),
                'progress_emissions' => $runtime->progressEmissionCount(),
                'peak_buffered_bytes' => $runtime->peakBufferedBytes(),
                'final_retained_evidence_bytes' => $runtime->finalRetainedEvidenceBytes(),
            ];
            $attemptPresent = null !== $receipt->attemptIdentity();
            $responsePresent = null !== $egress->response();
            $failurePresent = null !== $egressFailure;
            $credentialCleanup = $credential->cleanupComplete();
            $possibleEffect = in_array($serviceOutcome, [ExternalServiceOutcome::CancelledAfterPossibleEffect, ExternalServiceOutcome::UncertainEffect], true);
        }

        return new ExternalServiceInspection(
            $protected,
            count($supplied->bindingSource()->bindings()),
            $binding->requiredCapabilities()->count(),
            count($policy->allowedOrigins()),
            $this->budget($policy->budget()),
            $policy->addressScope(),
            $policy->transportSecurity(),
            $policy->redirectMode(),
            $policy->proxyMode(),
            $policy->cookieMode(),
            $policy->credentialMode(),
            $serviceOutcome,
            $serviceLifecycle,
            $serviceFailureKind,
            $credentialOutcome,
            $credentialState,
            $egressOutcome,
            $egressLifecycle,
            $transferState,
            $cancellationState,
            $protocol,
            $egressFailureKind,
            $counts,
            $attemptPresent,
            $responsePresent,
            $failurePresent,
            $credentialCleanup,
            $possibleEffect,
        );
    }

    private function compareProfile(ExternalServiceApplicationProfile $expected, ExternalServiceApplicationProfile $supplied): void
    {
        $expectedSource = $expected->bindingSource();
        $suppliedSource = $supplied->bindingSource();
        $expectedArtifact = $expected->bindingArtifact();
        $suppliedArtifact = $supplied->bindingArtifact();
        $expectedBinding = $expected->binding();
        $suppliedBinding = $supplied->binding();
        if (!$expected->identity()->equals($supplied->identity())
            || !$expectedSource->identity()->equals($suppliedSource->identity())
            || count($expectedSource->bindings()) !== count($suppliedSource->bindings())
            || !$expectedSource->equals($suppliedSource)
            || !$expectedArtifact->sourceIdentity()->equals($suppliedArtifact->sourceIdentity())
            || !hash_equals($expectedArtifact->sourceSha256(), $suppliedArtifact->sourceSha256())
            || !hash_equals($expectedArtifact->artifactSha256(), $suppliedArtifact->artifactSha256())
            || !hash_equals($expectedArtifact->canonicalBytes(), $suppliedArtifact->canonicalBytes())) {
            throw ExternalServiceInspectionBindingException::mismatch('artifact_binding');
        }
        if (!$expectedBinding->identity()->equals($suppliedBinding->identity())
            || !$expectedBinding->equals($suppliedBinding)
            || !$expectedBinding->profile()->equals($suppliedBinding->profile())
            || !$expectedBinding->endpoint()->equals($suppliedBinding->endpoint())
            || !$expectedBinding->credentialReference()->source()->equals($suppliedBinding->credentialReference()->source())
            || !$expectedBinding->credentialReference()->equals($suppliedBinding->credentialReference())
            || $expectedBinding->requiredCapabilities()->all() !== $suppliedBinding->requiredCapabilities()->all()
            || !hash_equals($this->capabilitySha256($expectedBinding->requiredCapabilities()->all()), $this->capabilitySha256($suppliedBinding->requiredCapabilities()->all()))) {
            throw ExternalServiceInspectionBindingException::mismatch('selected_binding');
        }

        try {
            $currentNetwork = (new NetworkEgressApplicationProfileFactory())->create(
                $supplied->networkEgressProfile()->source(),
                $supplied->networkEgressProfile()->artifact()->canonicalBytes(),
            );
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw ExternalServiceInspectionBindingException::mismatch('network_profile', $failure);
        }
        $network = $supplied->networkEgressProfile();
        $currentPolicy = $currentNetwork->source();
        $policy = $network->source();
        $currentArtifact = $currentNetwork->artifact();
        $artifact = $network->artifact();
        if (!$expected->networkEgressProfile()->identity()->equals($network->identity())
            || !$currentNetwork->identity()->equals($network->identity())
            || !$currentPolicy->identity()->equals($policy->identity())
            || !$currentPolicy->equals($policy)
            || !$currentArtifact->policyIdentity()->equals($artifact->policyIdentity())
            || !hash_equals($currentArtifact->sourceSha256(), $artifact->sourceSha256())
            || !hash_equals($currentArtifact->artifactSha256(), $artifact->artifactSha256())
            || !hash_equals($currentArtifact->canonicalBytes(), $artifact->canonicalBytes())
            || !hash_equals($this->capabilitySha256($currentPolicy->requiredCapabilities()->all()), $this->capabilitySha256($policy->requiredCapabilities()->all()))
            || $currentPolicy->addressScope() !== $policy->addressScope()
            || $currentPolicy->transportSecurity() !== $policy->transportSecurity()
            || $currentPolicy->redirectMode() !== $policy->redirectMode()
            || $currentPolicy->proxyMode() !== $policy->proxyMode()
            || $currentPolicy->cookieMode() !== $policy->cookieMode()
            || $currentPolicy->credentialMode() !== $policy->credentialMode()) {
            throw ExternalServiceInspectionBindingException::mismatch('policy_binding');
        }
    }

    private function coherentReceipt(EgressReceipt $receipt): bool
    {
        $transfer = $receipt->transfer()->state();
        $cancellation = $receipt->cancellation()->state();
        $attempt = null !== $receipt->attemptIdentity();

        return match ($receipt->outcome()) {
            EgressOutcome::RejectedBeforeAttempt => 0 === $receipt->connectionAttemptCount() && !$attempt
                && EgressLifecycleState::Failed === $receipt->finalLifecycleState()
                && EgressTransferState::None === $transfer && CancellationState::NotRequested === $cancellation
                && NegotiatedProtocol::Unknown === $receipt->negotiatedProtocol(),
            EgressOutcome::FailedBeforeEffect => ((0 === $receipt->connectionAttemptCount() && !$attempt) || (0 < $receipt->connectionAttemptCount() && $attempt))
                && EgressLifecycleState::Failed === $receipt->finalLifecycleState() && EgressTransferState::None === $transfer
                && CancellationState::NotRequested === $cancellation,
            EgressOutcome::CancelledBeforeEffect => ((0 === $receipt->connectionAttemptCount() && !$attempt) || (0 < $receipt->connectionAttemptCount() && $attempt))
                && EgressLifecycleState::Cancelled === $receipt->finalLifecycleState() && EgressTransferState::None === $transfer
                && CancellationState::RequestedBeforeAttempt === $cancellation,
            EgressOutcome::Succeeded => 0 < $receipt->connectionAttemptCount() && $attempt
                && EgressLifecycleState::Succeeded === $receipt->finalLifecycleState()
                && EgressTransferState::ResponseReceived === $transfer && CancellationState::NotRequested === $cancellation,
            EgressOutcome::CancelledAfterPossibleEffect => 0 < $receipt->connectionAttemptCount() && $attempt
                && EgressLifecycleState::Cancelled === $receipt->finalLifecycleState() && EgressTransferState::None !== $transfer
                && CancellationState::RequestedAfterPossibleEffect === $cancellation,
            EgressOutcome::UncertainEffect => 0 < $receipt->connectionAttemptCount() && $attempt
                && EgressLifecycleState::Uncertain === $receipt->finalLifecycleState() && EgressTransferState::None !== $transfer
                && CancellationState::RequestedBeforeAttempt !== $cancellation,
        };
    }

    private function coherentRuntime(\Kongono\NetworkEgressRuntime\Execution\EgressRuntimeResult $runtime, EgressPolicySource $policy): bool
    {
        $receipt = $runtime->operationResult()->receipt();
        $budget = $policy->budget();
        $protocolCapability = match ($receipt->negotiatedProtocol()) {
            NegotiatedProtocol::Unknown => null,
            NegotiatedProtocol::Http11 => TransportCapability::Http11,
            NegotiatedProtocol::Http2 => TransportCapability::Http2,
            NegotiatedProtocol::Http3 => TransportCapability::Http3,
        };

        return $receipt->connectionAttemptCount() <= $budget->maximumConnectionAttempts()
            && $runtime->resolutionCandidateCount() <= $budget->maximumResolutionCandidates()
            && $receipt->transfer()->requestBytes() <= $budget->maximumRequestBytes()
            && $receipt->transfer()->responseBytes() <= $budget->maximumResponseBytes()
            && $runtime->peakBufferedBytes() <= $budget->maximumBufferedBytes()
            && $runtime->finalRetainedEvidenceBytes() <= $budget->maximumRetainedEvidenceBytes()
            && $runtime->elapsedMilliseconds() <= $budget->totalTimeoutMilliseconds()
            && array_all(
                $policy->requiredCapabilities()->all(),
                static fn(TransportCapability $capability): bool => $receipt->capabilities()->contains($capability),
            )
            && (null === $protocolCapability || $receipt->capabilities()->contains($protocolCapability));
    }

    private function mapOutcome(EgressOutcome $outcome): ExternalServiceOutcome
    {
        return match ($outcome) {
            EgressOutcome::RejectedBeforeAttempt => ExternalServiceOutcome::RejectedBeforeAttempt,
            EgressOutcome::Succeeded => ExternalServiceOutcome::Succeeded,
            EgressOutcome::FailedBeforeEffect => ExternalServiceOutcome::FailedBeforeEffect,
            EgressOutcome::CancelledBeforeEffect => ExternalServiceOutcome::CancelledBeforeEffect,
            EgressOutcome::CancelledAfterPossibleEffect => ExternalServiceOutcome::CancelledAfterPossibleEffect,
            EgressOutcome::UncertainEffect => ExternalServiceOutcome::UncertainEffect,
        };
    }

    private function mapFailureKind(?EgressFailureKind $kind): ?ExternalServiceFailureKind
    {
        return match ($kind) {
            null => null,
            EgressFailureKind::Validation => ExternalServiceFailureKind::Binding,
            EgressFailureKind::PolicyDenied => ExternalServiceFailureKind::Policy,
            EgressFailureKind::Resolution => ExternalServiceFailureKind::Resolution,
            EgressFailureKind::Cancellation => ExternalServiceFailureKind::Cancelled,
            EgressFailureKind::Cleanup => ExternalServiceFailureKind::Cleanup,
            EgressFailureKind::Connection, EgressFailureKind::Tls, EgressFailureKind::Protocol,
            EgressFailureKind::Redirect, EgressFailureKind::Budget, EgressFailureKind::Timeout,
            EgressFailureKind::Response, EgressFailureKind::UncertainEffect => ExternalServiceFailureKind::Transport,
        };
    }

    /** @param list<TransportCapability> $capabilities */
    private function capabilitySha256(array $capabilities): string
    {
        return hash('sha256', implode('', array_map(static fn(TransportCapability $capability): string => $capability->value . "\n", $capabilities)));
    }

    /** @return array<string, int> */
    private function budget(EgressBudget $budget): array
    {
        return [
            'maximum_connection_attempts' => $budget->maximumConnectionAttempts(),
            'maximum_redirects' => $budget->maximumRedirects(),
            'maximum_resolution_candidates' => $budget->maximumResolutionCandidates(),
            'maximum_request_bytes' => $budget->maximumRequestBytes(),
            'maximum_response_bytes' => $budget->maximumResponseBytes(),
            'maximum_header_count' => $budget->maximumHeaderCount(),
            'maximum_aggregate_header_bytes' => $budget->maximumAggregateHeaderBytes(),
            'maximum_buffered_bytes' => $budget->maximumBufferedBytes(),
            'maximum_retained_evidence_bytes' => $budget->maximumRetainedEvidenceBytes(),
            'connect_timeout_milliseconds' => $budget->connectTimeoutMilliseconds(),
            'first_byte_timeout_milliseconds' => $budget->firstByteTimeoutMilliseconds(),
            'total_timeout_milliseconds' => $budget->totalTimeoutMilliseconds(),
        ];
    }
}
