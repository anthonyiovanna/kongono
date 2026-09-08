<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressInspection\Inspection;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressApplication\Generated\GeneratedNetworkEgressWiring;
use Kongono\NetworkEgressApplication\Generated\NetworkEgressWiringValidator;
use Kongono\NetworkEgressContract\Budget\EgressBudget;
use Kongono\NetworkEgressContract\Capability\TransportCapability;
use Kongono\NetworkEgressContract\Outcome\EgressOperationResult;
use Kongono\NetworkEgressContract\Outcome\EgressOutcome;
use Kongono\NetworkEgressContract\Transport\NegotiatedProtocol;
use Kongono\NetworkEgressInspection\Exception\NetworkEgressInspectionBindingException;
use Kongono\NetworkEgressInspection\Exception\NetworkEgressRuntimeEvidenceException;
use Kongono\NetworkEgressRuntime\Execution\EgressRuntimeResult;
use Throwable;

/** Revalidates exact supplied evidence without loading code, replaying work, or reading messages. */
#[Api(ApiStatus::Experimental)]
final readonly class NetworkEgressInspector
{
    public function inspect(NetworkEgressInspectionRequest $request): NetworkEgressInspection
    {
        $profile = $request->profile();
        $supplied = $request->wiring();
        try {
            $validated = (new NetworkEgressWiringValidator())->validateCurrent($profile, $supplied->canonicalBytes());
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw NetworkEgressInspectionBindingException::mismatch('profile_wiring', $failure);
        }
        if (!$this->sameWiring($validated, $supplied)) {
            throw NetworkEgressInspectionBindingException::mismatch('profile_wiring');
        }

        $source = $profile->source();
        $artifact = $profile->artifact();
        if (!$profile->identity()->equals($supplied->applicationProfileIdentity())
            || !$source->identity()->equals($supplied->policyIdentity())
            || !$artifact->policyIdentity()->equals($source->identity())
            || !$artifact->policyIdentity()->equals($supplied->policyIdentity())) {
            throw NetworkEgressInspectionBindingException::mismatch('profile_binding');
        }
        if (!hash_equals($artifact->artifactSha256(), $supplied->artifactSha256())
            || !hash_equals($artifact->sourceSha256(), $supplied->sourceSha256())
            || !hash_equals($this->capabilitySha256($source->requiredCapabilities()->all()), $supplied->requiredCapabilitiesSha256())) {
            throw NetworkEgressInspectionBindingException::mismatch('artifact_binding');
        }

        $operation = $request->operation();
        $runtime = $request->runtimeResult();
        $operationDigest = null;
        $operationResult = null;
        if (null !== $operation && null !== $runtime) {
            $operationResult = $runtime->operationResult();
            $receipt = $operationResult->receipt();
            if (!$operation->policyReference()->equals($artifact->policyReference())
                || !$operation->budget()->equals($source->budget())
                || !$this->originAllowed($operation->origin(), $source->allowedOrigins())
                || !$receipt->operationIdentity()->equals($operation->identity())
                || !$this->capabilitiesCover($receipt->capabilities()->all(), $source->requiredCapabilities()->all())
                || !$this->coherentTerminalResult($operationResult)
                || !$this->coherentRuntimeEvidence($runtime, $operation->budget())) {
                throw NetworkEgressRuntimeEvidenceException::mismatch('runtime_binding');
            }
            $operationDigest = hash('sha256', $operation->identity()->value());
        }

        $budget = $source->budget();
        $receipt = $operationResult?->receipt();
        $transfer = $receipt?->transfer();
        $cancellation = $receipt?->cancellation();

        return new NetworkEgressInspection(
            $profile->identity()->value(),
            hash('sha256', $source->identity()->value()),
            $artifact->sourceSha256(),
            $artifact->artifactSha256(),
            $supplied->requiredCapabilitiesSha256(),
            $supplied->implementationSha256(),
            $supplied->generatedWiringSha256(),
            $operationDigest,
            count($source->allowedOrigins()),
            $source->requiredCapabilities()->count(),
            $budget->maximumConnectionAttempts(),
            $budget->maximumRedirects(),
            $budget->maximumResolutionCandidates(),
            $source->addressScope(),
            $source->transportSecurity(),
            $source->redirectMode(),
            $source->proxyMode(),
            $source->cookieMode(),
            $source->credentialMode(),
            $receipt?->outcome(),
            $receipt?->finalLifecycleState(),
            $transfer?->state(),
            $cancellation?->state(),
            $receipt?->negotiatedProtocol(),
            $receipt?->connectionAttemptCount() ?? 0,
            $transfer?->requestBytes() ?? 0,
            $transfer?->responseBytes() ?? 0,
            $cancellation?->observationSequence() ?? 0,
            $runtime?->elapsedMilliseconds() ?? 0,
            $runtime?->resolutionCandidateCount() ?? 0,
            $runtime?->progressEmissionCount() ?? 0,
            $runtime?->peakBufferedBytes() ?? 0,
            $runtime?->finalRetainedEvidenceBytes() ?? 0,
            null !== $receipt?->attemptIdentity(),
            null !== $operationResult?->response(),
            null !== $operationResult?->failure(),
            null !== $runtime,
        );
    }

    private function sameWiring(GeneratedNetworkEgressWiring $left, GeneratedNetworkEgressWiring $right): bool
    {
        return $left->applicationProfileIdentity()->equals($right->applicationProfileIdentity())
            && $left->policyIdentity()->equals($right->policyIdentity())
            && hash_equals($left->artifactSha256(), $right->artifactSha256())
            && hash_equals($left->sourceSha256(), $right->sourceSha256())
            && hash_equals($left->requiredCapabilitiesSha256(), $right->requiredCapabilitiesSha256())
            && hash_equals($left->implementationSha256(), $right->implementationSha256())
            && $left->generatedNamespace() === $right->generatedNamespace()
            && $left->generatedClass() === $right->generatedClass()
            && hash_equals($left->canonicalBytes(), $right->canonicalBytes())
            && hash_equals($left->generatedWiringSha256(), $right->generatedWiringSha256());
    }

    /** @param list<\Kongono\NetworkEgressContract\Origin\CanonicalOrigin> $allowed */
    private function originAllowed(\Kongono\NetworkEgressContract\Origin\CanonicalOrigin $operationOrigin, array $allowed): bool
    {
        return array_any($allowed, static fn(\Kongono\NetworkEgressContract\Origin\CanonicalOrigin $origin): bool => $operationOrigin->equals($origin));
    }

    /**
     * @param list<TransportCapability> $actual
     * @param list<TransportCapability> $required
     */
    private function capabilitiesCover(array $actual, array $required): bool
    {
        return array_all($required, static fn(TransportCapability $capability): bool => in_array($capability, $actual, true));
    }

    private function coherentTerminalResult(EgressOperationResult $result): bool
    {
        $receipt = $result->receipt();
        $success = EgressOutcome::Succeeded === $receipt->outcome();

        return $success === (null !== $result->response())
            && $success !== (null !== $result->failure())
            && in_array($receipt->outcome(), EgressOutcome::cases(), true);
    }

    private function coherentRuntimeEvidence(EgressRuntimeResult $runtime, EgressBudget $budget): bool
    {
        $receipt = $runtime->operationResult()->receipt();
        $transfer = $receipt->transfer();
        $protocolCapability = match ($receipt->negotiatedProtocol()) {
            NegotiatedProtocol::Unknown => null,
            NegotiatedProtocol::Http11 => TransportCapability::Http11,
            NegotiatedProtocol::Http2 => TransportCapability::Http2,
            NegotiatedProtocol::Http3 => TransportCapability::Http3,
        };

        return $receipt->connectionAttemptCount() <= $budget->maximumConnectionAttempts()
            && $runtime->resolutionCandidateCount() <= $budget->maximumResolutionCandidates()
            && $transfer->requestBytes() <= $budget->maximumRequestBytes()
            && $transfer->responseBytes() <= $budget->maximumResponseBytes()
            && $runtime->peakBufferedBytes() <= $budget->maximumBufferedBytes()
            && $runtime->finalRetainedEvidenceBytes() <= $budget->maximumRetainedEvidenceBytes()
            && $runtime->elapsedMilliseconds() <= $budget->totalTimeoutMilliseconds()
            && (null === $protocolCapability || in_array($protocolCapability, $receipt->capabilities()->all(), true));
    }

    /**
     * @param list<TransportCapability> $capabilities
     */
    private function capabilitySha256(array $capabilities): string
    {
        $bytes = '';
        foreach ($capabilities as $capability) {
            $bytes .= $capability->value . "\n";
        }

        return hash('sha256', $bytes);
    }
}
