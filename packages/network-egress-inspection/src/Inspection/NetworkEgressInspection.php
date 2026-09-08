<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressInspection\Inspection;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressContract\Cancellation\CancellationState;
use Kongono\NetworkEgressContract\Lifecycle\EgressLifecycleState;
use Kongono\NetworkEgressContract\Outcome\EgressOutcome;
use Kongono\NetworkEgressContract\Transfer\EgressTransferState;
use Kongono\NetworkEgressContract\Transport\NegotiatedProtocol;
use Kongono\NetworkEgressInspection\Exception\InvalidNetworkEgressInspectionException;
use Kongono\NetworkEgressInspection\Internal\CanonicalJson;
use Kongono\NetworkEgressPolicyBuild\Policy\AddressScope;
use Kongono\NetworkEgressPolicyBuild\Policy\CookieMode;
use Kongono\NetworkEgressPolicyBuild\Policy\CredentialMode;
use Kongono\NetworkEgressPolicyBuild\Policy\ProxyMode;
use Kongono\NetworkEgressPolicyBuild\Policy\RedirectMode;
use Kongono\NetworkEgressPolicyBuild\Policy\TransportSecurity;

/** Carries only protected digests, closed tokens, Safe aggregates, and fixed limitations. */
#[Api(ApiStatus::Experimental)]
final readonly class NetworkEgressInspection
{
    public const string SCHEMA = 'kongono.network-egress-inspection';
    public const int VERSION = 1;
    public const string PROFILE_VERSION = 'network-egress-application-profile-v1';
    public const string WIRING_VERSION = 'network-egress-application-generated-wiring-v1';
    public const string SCOPE = 'network-egress-v1';
    public const string DISCLOSURE = 'safe-aggregate-only';
    public const string SUPPLIED_PROFILE_WIRING_CURRENTNESS = 'caller-relative-exact-comparison-only';
    public const string APPLICATION_STATE = 'not-observed';
    public const string EXTERNAL_AUTHORITY_CURRENTNESS = 'not-established';
    public const string NETWORK_STATE = 'not-observed';
    public const string RECEIPT_RESULT_TRUTH = 'bounded-claim-not-external-truth';
    public const string NETWORK_EFFECT = 'not-established';
    public const string REPLAY = 'not-performed';
    public const string REPAIR = 'not-performed';
    public const string GRAPH_RELATIONSHIP = 'not-established';
    public const string TRUSTED_CODE_ISOLATION = 'not-sandboxed';
    public const string GENERATED_PHP_LOADING = 'not-performed';
    public const string AUTHORITY = 'evidence-only';

    private string $identity;

    public function __construct(
        private string $applicationProfileSha256,
        private string $policyIdentitySha256,
        private string $sourceSha256,
        private string $artifactSha256,
        private string $requiredCapabilitiesSha256,
        private string $generatorImplementationSha256,
        private string $generatedWiringSha256,
        private ?string $operationIdentitySha256,
        private int $allowedOriginCount,
        private int $requiredCapabilityCount,
        private int $maximumConnectionAttempts,
        private int $maximumRedirects,
        private int $maximumResolutionCandidates,
        private AddressScope $addressScope,
        private TransportSecurity $transportSecurity,
        private RedirectMode $redirectMode,
        private ProxyMode $proxyMode,
        private CookieMode $cookieMode,
        private CredentialMode $credentialMode,
        private ?EgressOutcome $runtimeOutcome = null,
        private ?EgressLifecycleState $runtimeLifecycle = null,
        private ?EgressTransferState $transferState = null,
        private ?CancellationState $cancellationState = null,
        private ?NegotiatedProtocol $negotiatedProtocol = null,
        private int $connectionAttemptCount = 0,
        private int $requestBytes = 0,
        private int $responseBytes = 0,
        private int $cancellationObservationSequence = 0,
        private int $elapsedMilliseconds = 0,
        private int $resolutionCandidateCount = 0,
        private int $progressEmissionCount = 0,
        private int $peakBufferedBytes = 0,
        private int $finalRetainedEvidenceBytes = 0,
        private bool $attemptIdentityPresent = false,
        private bool $responsePresent = false,
        private bool $failurePresent = false,
        private bool $cleanupComplete = false,
    ) {
        $requiredDigests = [
            $applicationProfileSha256,
            $policyIdentitySha256,
            $sourceSha256,
            $artifactSha256,
            $requiredCapabilitiesSha256,
            $generatorImplementationSha256,
            $generatedWiringSha256,
        ];
        if (array_any($requiredDigests, static fn(string $digest): bool => 1 !== preg_match('/\A[a-f0-9]{64}\z/D', $digest))
            || (null !== $operationIdentitySha256 && 1 !== preg_match('/\A[a-f0-9]{64}\z/D', $operationIdentitySha256))) {
            throw InvalidNetworkEgressInspectionException::invalid('digest');
        }
        $counts = [
            $allowedOriginCount,
            $requiredCapabilityCount,
            $maximumConnectionAttempts,
            $maximumRedirects,
            $maximumResolutionCandidates,
            $connectionAttemptCount,
            $requestBytes,
            $responseBytes,
            $cancellationObservationSequence,
            $elapsedMilliseconds,
            $resolutionCandidateCount,
            $progressEmissionCount,
            $peakBufferedBytes,
            $finalRetainedEvidenceBytes,
        ];
        if (array_any($counts, static fn(int $count): bool => $count < 0)) {
            throw InvalidNetworkEgressInspectionException::invalid('aggregate');
        }
        $runtimeSupplied = null !== $runtimeOutcome;
        if ($runtimeSupplied !== (null !== $operationIdentitySha256)
            || $runtimeSupplied !== (null !== $runtimeLifecycle)
            || $runtimeSupplied !== (null !== $transferState)
            || $runtimeSupplied !== (null !== $cancellationState)
            || $runtimeSupplied !== (null !== $negotiatedProtocol)
            || (!$runtimeSupplied && (0 !== $connectionAttemptCount || 0 !== $requestBytes || 0 !== $responseBytes
                || 0 !== $cancellationObservationSequence || 0 !== $elapsedMilliseconds || 0 !== $resolutionCandidateCount
                || 0 !== $progressEmissionCount || 0 !== $peakBufferedBytes || 0 !== $finalRetainedEvidenceBytes
                || $attemptIdentityPresent || $responsePresent || $failurePresent || $cleanupComplete))
            || ($runtimeSupplied && !$cleanupComplete)
            || ($runtimeSupplied && ($responsePresent === $failurePresent))
            || ($runtimeSupplied && (EgressOutcome::Succeeded === $runtimeOutcome) !== $responsePresent)) {
            throw InvalidNetworkEgressInspectionException::invalid('runtime_structure');
        }
        $this->identity = hash('sha256', CanonicalJson::encode(['schema' => self::SCHEMA, 'version' => self::VERSION] + $this->payload()));
    }

    public function identity(): string
    {
        return $this->identity;
    }

    public function applicationProfileSha256(): string
    {
        return $this->applicationProfileSha256;
    }

    public function policyIdentitySha256(): string
    {
        return $this->policyIdentitySha256;
    }

    public function sourceSha256(): string
    {
        return $this->sourceSha256;
    }

    public function artifactSha256(): string
    {
        return $this->artifactSha256;
    }

    public function requiredCapabilitiesSha256(): string
    {
        return $this->requiredCapabilitiesSha256;
    }

    public function generatorImplementationSha256(): string
    {
        return $this->generatorImplementationSha256;
    }

    public function generatedWiringSha256(): string
    {
        return $this->generatedWiringSha256;
    }

    public function operationIdentitySha256(): ?string
    {
        return $this->operationIdentitySha256;
    }

    public function allowedOriginCount(): int
    {
        return $this->allowedOriginCount;
    }

    public function requiredCapabilityCount(): int
    {
        return $this->requiredCapabilityCount;
    }

    public function maximumConnectionAttempts(): int
    {
        return $this->maximumConnectionAttempts;
    }

    public function maximumRedirects(): int
    {
        return $this->maximumRedirects;
    }

    public function maximumResolutionCandidates(): int
    {
        return $this->maximumResolutionCandidates;
    }

    public function addressScope(): AddressScope
    {
        return $this->addressScope;
    }

    public function transportSecurity(): TransportSecurity
    {
        return $this->transportSecurity;
    }

    public function redirectMode(): RedirectMode
    {
        return $this->redirectMode;
    }

    public function proxyMode(): ProxyMode
    {
        return $this->proxyMode;
    }

    public function cookieMode(): CookieMode
    {
        return $this->cookieMode;
    }

    public function credentialMode(): CredentialMode
    {
        return $this->credentialMode;
    }

    public function runtimeEvidenceSupplied(): bool
    {
        return null !== $this->runtimeOutcome;
    }

    public function runtimeOutcome(): ?EgressOutcome
    {
        return $this->runtimeOutcome;
    }

    public function runtimeLifecycle(): ?EgressLifecycleState
    {
        return $this->runtimeLifecycle;
    }

    public function transferState(): ?EgressTransferState
    {
        return $this->transferState;
    }

    public function cancellationState(): ?CancellationState
    {
        return $this->cancellationState;
    }

    public function negotiatedProtocol(): ?NegotiatedProtocol
    {
        return $this->negotiatedProtocol;
    }

    public function connectionAttemptCount(): int
    {
        return $this->connectionAttemptCount;
    }

    public function requestBytes(): int
    {
        return $this->requestBytes;
    }

    public function responseBytes(): int
    {
        return $this->responseBytes;
    }

    public function cancellationObservationSequence(): int
    {
        return $this->cancellationObservationSequence;
    }

    public function elapsedMilliseconds(): int
    {
        return $this->elapsedMilliseconds;
    }

    public function resolutionCandidateCount(): int
    {
        return $this->resolutionCandidateCount;
    }

    public function progressEmissionCount(): int
    {
        return $this->progressEmissionCount;
    }

    public function peakBufferedBytes(): int
    {
        return $this->peakBufferedBytes;
    }

    public function finalRetainedEvidenceBytes(): int
    {
        return $this->finalRetainedEvidenceBytes;
    }

    public function attemptIdentityPresent(): bool
    {
        return $this->attemptIdentityPresent;
    }

    public function responsePresent(): bool
    {
        return $this->responsePresent;
    }

    public function failurePresent(): bool
    {
        return $this->failurePresent;
    }

    public function cleanupComplete(): bool
    {
        return $this->cleanupComplete;
    }

    public function scope(): string
    {
        return self::SCOPE;
    }

    public function disclosure(): string
    {
        return self::DISCLOSURE;
    }

    public function authority(): string
    {
        return self::AUTHORITY;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['schema' => self::SCHEMA, 'version' => self::VERSION, 'inspection_identity' => $this->identity] + $this->payload();
    }

    public function canonicalBytes(): string
    {
        return CanonicalJson::encode($this->toArray());
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        return [
            'protected_bindings' => [
                'application_profile_sha256' => $this->applicationProfileSha256,
                'policy_identity_sha256' => $this->policyIdentitySha256,
                'source_sha256' => $this->sourceSha256,
                'artifact_sha256' => $this->artifactSha256,
                'required_capabilities_sha256' => $this->requiredCapabilitiesSha256,
                'generator_implementation_sha256' => $this->generatorImplementationSha256,
                'generated_wiring_sha256' => $this->generatedWiringSha256,
                'operation_identity_sha256' => $this->operationIdentitySha256,
                'disclosure' => 'protected',
            ],
            'profile_version' => self::PROFILE_VERSION,
            'wiring_version' => self::WIRING_VERSION,
            'allowed_origin_count' => $this->allowedOriginCount,
            'required_capability_count' => $this->requiredCapabilityCount,
            'maximum_connection_attempts' => $this->maximumConnectionAttempts,
            'maximum_redirects' => $this->maximumRedirects,
            'maximum_resolution_candidates' => $this->maximumResolutionCandidates,
            'policy_posture' => [
                'address_scope' => $this->addressScope->value,
                'transport_security' => $this->transportSecurity->value,
                'redirect_mode' => $this->redirectMode->value,
                'proxy_mode' => $this->proxyMode->value,
                'cookie_mode' => $this->cookieMode->value,
                'credential_mode' => $this->credentialMode->value,
            ],
            'runtime_evidence_supplied' => $this->runtimeEvidenceSupplied(),
            'runtime_summary' => $this->runtimeSummary(),
            'limitations' => [
                'scope' => self::SCOPE,
                'disclosure' => self::DISCLOSURE,
                'supplied_profile_wiring_currentness' => self::SUPPLIED_PROFILE_WIRING_CURRENTNESS,
                'live_application_state' => self::APPLICATION_STATE,
                'external_authority_currentness' => self::EXTERNAL_AUTHORITY_CURRENTNESS,
                'external_network_state' => self::NETWORK_STATE,
                'receipt_result_evidence' => self::RECEIPT_RESULT_TRUTH,
                'network_effect' => self::NETWORK_EFFECT,
                'replay' => self::REPLAY,
                'repair' => self::REPAIR,
                'network_egress_to_graph_relationship' => self::GRAPH_RELATIONSHIP,
                'trusted_generated_in_process_php' => self::TRUSTED_CODE_ISOLATION,
                'generated_php_loading' => self::GENERATED_PHP_LOADING,
                'authority' => self::AUTHORITY,
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function runtimeSummary(): array
    {
        if (null === $this->runtimeOutcome || null === $this->runtimeLifecycle || null === $this->transferState
            || null === $this->cancellationState || null === $this->negotiatedProtocol) {
            return [];
        }

        return [
            'outcome' => $this->runtimeOutcome->value,
            'terminal_lifecycle' => $this->runtimeLifecycle->value,
            'transfer_state' => $this->transferState->value,
            'cancellation_state' => $this->cancellationState->value,
            'negotiated_protocol' => $this->negotiatedProtocol->value,
            'connection_attempt_count' => $this->connectionAttemptCount,
            'request_bytes' => $this->requestBytes,
            'response_bytes' => $this->responseBytes,
            'cancellation_observation_sequence' => $this->cancellationObservationSequence,
            'elapsed_milliseconds' => $this->elapsedMilliseconds,
            'resolution_candidate_count' => $this->resolutionCandidateCount,
            'progress_emission_count' => $this->progressEmissionCount,
            'peak_buffered_bytes' => $this->peakBufferedBytes,
            'final_retained_evidence_bytes' => $this->finalRetainedEvidenceBytes,
            'attempt_identity_present' => $this->attemptIdentityPresent,
            'response_present' => $this->responsePresent,
            'failure_present' => $this->failurePresent,
            'cleanup_complete' => $this->cleanupComplete,
        ];
    }
}
