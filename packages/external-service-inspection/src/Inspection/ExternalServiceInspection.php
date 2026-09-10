<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceInspection\Inspection;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceContract\Failure\ExternalServiceFailureKind;
use Kongono\ExternalServiceContract\Lifecycle\ExternalServiceLifecycleState;
use Kongono\ExternalServiceContract\Outcome\ExternalServiceOutcome;
use Kongono\ExternalServiceCredentialRuntime\Lifecycle\CredentialLeaseRuntimeState;
use Kongono\ExternalServiceCredentialRuntime\Outcome\CredentialLeaseRuntimeOutcome;
use Kongono\ExternalServiceInspection\Exception\InvalidExternalServiceInspectionException;
use Kongono\ExternalServiceInspection\Internal\CanonicalJson;
use Kongono\NetworkEgressContract\Cancellation\CancellationState;
use Kongono\NetworkEgressContract\Failure\EgressFailureKind;
use Kongono\NetworkEgressContract\Lifecycle\EgressLifecycleState;
use Kongono\NetworkEgressContract\Outcome\EgressOutcome;
use Kongono\NetworkEgressContract\Transfer\EgressTransferState;
use Kongono\NetworkEgressContract\Transport\NegotiatedProtocol;
use Kongono\NetworkEgressPolicyBuild\Policy\AddressScope;
use Kongono\NetworkEgressPolicyBuild\Policy\CookieMode;
use Kongono\NetworkEgressPolicyBuild\Policy\CredentialMode;
use Kongono\NetworkEgressPolicyBuild\Policy\ProxyMode;
use Kongono\NetworkEgressPolicyBuild\Policy\RedirectMode;
use Kongono\NetworkEgressPolicyBuild\Policy\TransportSecurity;

/** Carries only protected digests, closed tokens, Safe aggregates, and fixed limitations. */
#[Api(ApiStatus::Experimental)]
final readonly class ExternalServiceInspection
{
    public const string SCHEMA = 'kongono.external-service-inspection';
    public const int VERSION = 1;
    public const string SCOPE = 'external-service-v1';
    public const string DISCLOSURE = 'safe-aggregate-only';
    public const string AUTHORITY = 'evidence-only';

    private string $identity;

    /**
     * @param array<string, string> $protectedBindings
     * @param array<string, int> $policyBudget
     * @param array<string, int> $resultCounts
     */
    public function __construct(
        private array $protectedBindings,
        private int $bindingCount,
        private int $requiredCapabilityCount,
        private int $allowedOriginCount,
        private array $policyBudget,
        private AddressScope $addressScope,
        private TransportSecurity $transportSecurity,
        private RedirectMode $redirectMode,
        private ProxyMode $proxyMode,
        private CookieMode $cookieMode,
        private CredentialMode $credentialMode,
        private ?ExternalServiceOutcome $serviceOutcome = null,
        private ?ExternalServiceLifecycleState $serviceLifecycle = null,
        private ?ExternalServiceFailureKind $serviceFailureKind = null,
        private ?CredentialLeaseRuntimeOutcome $credentialOutcome = null,
        private ?CredentialLeaseRuntimeState $credentialState = null,
        private ?EgressOutcome $egressOutcome = null,
        private ?EgressLifecycleState $egressLifecycle = null,
        private ?EgressTransferState $transferState = null,
        private ?CancellationState $cancellationState = null,
        private ?NegotiatedProtocol $negotiatedProtocol = null,
        private ?EgressFailureKind $egressFailureKind = null,
        private array $resultCounts = [],
        private bool $attemptIdentityPresent = false,
        private bool $responsePresent = false,
        private bool $failurePresent = false,
        private bool $credentialCleanupComplete = false,
        private bool $possibleEffectUncertainty = false,
    ) {
        $this->validateBindings();
        $this->validateCounts();
        $this->validateResultStructure();
        $this->identity = hash('sha256', CanonicalJson::encode(['schema' => self::SCHEMA, 'version' => self::VERSION] + $this->payload()));
    }

    public function identity(): string
    {
        return $this->identity;
    }

    /** @return array<string, string> */
    public function protectedBindings(): array
    {
        return [...$this->protectedBindings];
    }

    public function bindingCount(): int
    {
        return $this->bindingCount;
    }

    public function requiredCapabilityCount(): int
    {
        return $this->requiredCapabilityCount;
    }

    public function allowedOriginCount(): int
    {
        return $this->allowedOriginCount;
    }

    /** @return array<string, int> */
    public function policyBudget(): array
    {
        return [...$this->policyBudget];
    }

    public function resultSupplied(): bool
    {
        return null !== $this->serviceOutcome;
    }

    public function serviceOutcome(): ?ExternalServiceOutcome
    {
        return $this->serviceOutcome;
    }

    public function serviceLifecycle(): ?ExternalServiceLifecycleState
    {
        return $this->serviceLifecycle;
    }

    public function credentialOutcome(): ?CredentialLeaseRuntimeOutcome
    {
        return $this->credentialOutcome;
    }

    public function credentialState(): ?CredentialLeaseRuntimeState
    {
        return $this->credentialState;
    }

    public function egressOutcome(): ?EgressOutcome
    {
        return $this->egressOutcome;
    }

    /** @return array<string, int> */
    public function resultCounts(): array
    {
        return [...$this->resultCounts];
    }

    public function responsePresent(): bool
    {
        return $this->responsePresent;
    }

    public function failurePresent(): bool
    {
        return $this->failurePresent;
    }

    public function credentialCleanupComplete(): bool
    {
        return $this->credentialCleanupComplete;
    }

    public function possibleEffectUncertainty(): bool
    {
        return $this->possibleEffectUncertainty;
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

    private function validateBindings(): void
    {
        $keys = [
            'application_profile_sha256', 'binding_source_identity_sha256', 'binding_artifact_source_sha256',
            'binding_artifact_sha256', 'selected_binding_sha256', 'service_profile_sha256', 'endpoint_sha256',
            'credential_source_sha256', 'credential_reference_sha256', 'required_capabilities_sha256',
            'network_profile_sha256', 'network_policy_identity_sha256', 'network_source_sha256',
            'network_artifact_sha256', 'operation_identity_sha256', 'receipt_identity_sha256',
        ];
        if ($keys !== array_keys($this->protectedBindings)) {
            throw InvalidExternalServiceInspectionException::invalid('digest');
        }
        foreach ($this->protectedBindings as $key => $digest) {
            if ('' === $digest && in_array($key, ['operation_identity_sha256', 'receipt_identity_sha256'], true)) {
                continue;
            }
            if (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $digest)) {
                throw InvalidExternalServiceInspectionException::invalid('digest');
            }
        }
    }

    private function validateCounts(): void
    {
        $budgetKeys = [
            'maximum_connection_attempts', 'maximum_redirects', 'maximum_resolution_candidates',
            'maximum_request_bytes', 'maximum_response_bytes', 'maximum_header_count',
            'maximum_aggregate_header_bytes', 'maximum_buffered_bytes', 'maximum_retained_evidence_bytes',
            'connect_timeout_milliseconds', 'first_byte_timeout_milliseconds', 'total_timeout_milliseconds',
        ];
        $resultKeys = [
            'credential_elapsed_milliseconds', 'credential_acquisition_attempts', 'credential_material_access_attempts',
            'credential_retirement_attempts', 'connection_attempts', 'request_bytes', 'response_bytes',
            'cancellation_observation_sequence', 'egress_elapsed_milliseconds', 'resolution_candidates',
            'progress_emissions', 'peak_buffered_bytes', 'final_retained_evidence_bytes',
        ];
        if ($budgetKeys !== array_keys($this->policyBudget) || ([] !== $this->resultCounts && $resultKeys !== array_keys($this->resultCounts))) {
            throw InvalidExternalServiceInspectionException::invalid('aggregate');
        }
        foreach ([$this->bindingCount, $this->requiredCapabilityCount, $this->allowedOriginCount, ...array_values($this->policyBudget), ...array_values($this->resultCounts)] as $count) {
            if ($count < 0) {
                throw InvalidExternalServiceInspectionException::invalid('aggregate');
            }
        }
    }

    private function validateResultStructure(): void
    {
        $supplied = null !== $this->serviceOutcome;
        $required = [
            $this->serviceLifecycle, $this->credentialOutcome, $this->credentialState, $this->egressOutcome,
            $this->egressLifecycle, $this->transferState, $this->cancellationState, $this->negotiatedProtocol,
        ];
        if ($supplied !== array_all($required, static fn(?object $value): bool => null !== $value)
            || $supplied !== ([] !== $this->resultCounts)
            || $supplied !== ('' !== $this->protectedBindings['operation_identity_sha256'])
            || $supplied !== ('' !== $this->protectedBindings['receipt_identity_sha256'])
            || (!$supplied && (null !== $this->serviceFailureKind || null !== $this->egressFailureKind || $this->attemptIdentityPresent
                || $this->responsePresent || $this->failurePresent || $this->credentialCleanupComplete || $this->possibleEffectUncertainty))) {
            throw InvalidExternalServiceInspectionException::invalid('result_structure');
        }
        if (!$supplied) {
            return;
        }
        $success = ExternalServiceOutcome::Succeeded === $this->serviceOutcome;
        $uncertain = in_array($this->serviceOutcome, [ExternalServiceOutcome::CancelledAfterPossibleEffect, ExternalServiceOutcome::UncertainEffect], true);
        if ($success !== $this->responsePresent || $success === $this->failurePresent
            || $success === (null !== $this->serviceFailureKind) || $success === (null !== $this->egressFailureKind)
            || CredentialLeaseRuntimeOutcome::Succeeded !== $this->credentialOutcome
            || CredentialLeaseRuntimeState::Retired !== $this->credentialState
            || !$this->credentialCleanupComplete || $uncertain !== $this->possibleEffectUncertainty) {
            throw InvalidExternalServiceInspectionException::invalid('semantic_tokens');
        }
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        return [
            'protected_bindings' => [...$this->protectedBindings, 'disclosure' => 'protected'],
            'binding_count' => $this->bindingCount,
            'required_capability_count' => $this->requiredCapabilityCount,
            'allowed_origin_count' => $this->allowedOriginCount,
            'policy_budget' => [...$this->policyBudget],
            'policy_posture' => [
                'address_scope' => $this->addressScope->value,
                'transport_security' => $this->transportSecurity->value,
                'redirect_mode' => $this->redirectMode->value,
                'proxy_mode' => $this->proxyMode->value,
                'cookie_mode' => $this->cookieMode->value,
                'credential_mode' => $this->credentialMode->value,
            ],
            'completed_result_supplied' => $this->resultSupplied(),
            'completed_result_summary' => $this->resultSummary(),
            'limitations' => [
                'scope' => self::SCOPE,
                'disclosure' => self::DISCLOSURE,
                'supplied_profile_currentness' => 'caller-relative-exact-comparison-only',
                'live_application_state' => 'not-observed',
                'credential_availability_validity_material_source_state_physical_erasure' => 'not-observed',
                'credential_acquisition_use_retirement_by_inspection' => 'not-performed',
                'successful_retirement' => 'not-physical-erasure-or-network-effect-proof',
                'external_authority_currentness' => 'not-established',
                'resolver_dns_endpoint_route_transport_certificate_provider_account_state' => 'not-observed',
                'detached_result_receipt_evidence' => 'bounded-claim-not-external-truth',
                'network_provider_effect' => 'not-established',
                'retry' => 'not-authorized-or-performed',
                'replay' => 'not-performed',
                'repair' => 'not-performed',
                'certification' => 'not-performed',
                'external_service_to_graph_relationship' => 'not-established',
                'trusted_generated_in_process_php' => 'not-sandboxed',
                'generated_php_loading' => 'not-performed',
                'authority' => self::AUTHORITY,
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function resultSummary(): array
    {
        if (!$this->resultSupplied()) {
            return [];
        }

        return [
            'service_outcome' => $this->serviceOutcome?->value,
            'service_lifecycle' => $this->serviceLifecycle?->value,
            'service_failure_kind' => $this->serviceFailureKind?->value,
            'credential_outcome' => $this->credentialOutcome?->value,
            'credential_state' => $this->credentialState?->value,
            'credential_failure_kind' => null,
            'egress_outcome' => $this->egressOutcome?->value,
            'egress_lifecycle' => $this->egressLifecycle?->value,
            'transfer_state' => $this->transferState?->value,
            'cancellation_state' => $this->cancellationState?->value,
            'negotiated_protocol' => $this->negotiatedProtocol?->value,
            'egress_failure_kind' => $this->egressFailureKind?->value,
            'counts' => [...$this->resultCounts],
            'attempt_identity_present' => $this->attemptIdentityPresent,
            'response_present' => $this->responsePresent,
            'failure_present' => $this->failurePresent,
            'credential_cleanup_complete' => $this->credentialCleanupComplete,
            'possible_effect_uncertainty' => $this->possibleEffectUncertainty,
        ];
    }
}
