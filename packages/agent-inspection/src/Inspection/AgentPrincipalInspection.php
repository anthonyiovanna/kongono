<?php

declare(strict_types=1);

namespace Kongono\AgentInspection\Inspection;

use Kongono\AgentInspection\Exception\InvalidAgentInspectionException;
use Kongono\AgentInspection\Internal\CanonicalJson;
use Kongono\AgentPrincipalContract\ContractVersion;
use Kongono\AgentPrincipalContract\Visibility\AgentVisibility;
use Kongono\AgentRuntime\Budget\AgentRuntimeUsage;
use Kongono\AgentRuntime\Runtime\AgentRuntimeOutcome;
use Kongono\AgentRuntime\Runtime\AgentRuntimeState;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Carries only deterministic identities, aggregates, and honest limitation tokens. */
#[Api(ApiStatus::Experimental)]
final readonly class AgentPrincipalInspection
{
    public const string SCHEMA = 'kongono.agent-principal-inspection';
    public const int VERSION = 1;
    public const string SCOPE = 'agent-principal-v1';
    public const string DISCLOSURE = 'safe-aggregate-only';
    public const string PROFILE_CURRENTNESS = 'exact-supplied-profile';
    public const string EXTERNAL_AUTHORITY_CURRENTNESS = 'not-established';
    public const string AUTHORITY = 'evidence-only';
    public const string APPLICATION_STATE = 'not-observed';
    public const string PROVIDER_STATE = 'not-observed';
    public const string GRAPH_RELATIONSHIP = 'not-established';
    public const string TRUSTED_CODE_ISOLATION = 'not-sandboxed';

    private string $identity;

    public function __construct(
        private string $profileIdentity,
        private string $declarationArtifactSha256,
        private string $declarationIdentity,
        private string $admissionIdentity,
        private string $generatedWiringSha256,
        private ContractVersion $contractVersion,
        private int $declaredCapabilityCount,
        private int $requiredCapabilityCount,
        private int $optionalCapabilityCount,
        private int $permittedCapabilityCount,
        private int $deniedCapabilityCount,
        private int $constraintCount,
        private int $evidenceReferenceCount,
        private AgentVisibility $declaredVisibility,
        private ?AgentRuntimeOutcome $runtimeOutcome = null,
        private ?AgentRuntimeState $runtimeTerminalState = null,
        private ?AgentRuntimeUsage $runtimeUsage = null,
        private int $receiptCount = 0,
        private bool $capabilityResultPresent = false,
        private bool $validatedProposalPresent = false,
        private bool $cleanupCompletedWithoutFailure = false,
        private string $scope = self::SCOPE,
        private string $disclosure = self::DISCLOSURE,
        private string $profileCurrentness = self::PROFILE_CURRENTNESS,
        private string $externalAuthorityCurrentness = self::EXTERNAL_AUTHORITY_CURRENTNESS,
        private string $authority = self::AUTHORITY,
        private string $applicationState = self::APPLICATION_STATE,
        private string $providerState = self::PROVIDER_STATE,
        private string $graphRelationship = self::GRAPH_RELATIONSHIP,
        private string $trustedCodeIsolation = self::TRUSTED_CODE_ISOLATION,
    ) {
        foreach ([$profileIdentity, $declarationArtifactSha256, $admissionIdentity, $generatedWiringSha256] as $digest) {
            if (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $digest)) {
                throw InvalidAgentInspectionException::invalid('digest');
            }
        }
        if ('' === $declarationIdentity || strlen($declarationIdentity) > 128) {
            throw InvalidAgentInspectionException::invalid('identity');
        }
        $counts = [$declaredCapabilityCount, $requiredCapabilityCount, $optionalCapabilityCount, $permittedCapabilityCount, $deniedCapabilityCount, $constraintCount, $evidenceReferenceCount, $receiptCount];
        if (array_any($counts, static fn(int $count): bool => $count < 0)
            || $declaredCapabilityCount !== $requiredCapabilityCount + $optionalCapabilityCount
            || $permittedCapabilityCount > $declaredCapabilityCount
            || $deniedCapabilityCount > $declaredCapabilityCount) {
            throw InvalidAgentInspectionException::invalid('count');
        }
        $runtimeParts = [null !== $runtimeOutcome, null !== $runtimeTerminalState, null !== $runtimeUsage];
        if (count(array_filter($runtimeParts)) !== 0 && count(array_filter($runtimeParts)) !== 3) {
            throw InvalidAgentInspectionException::invalid('runtime_structure');
        }
        if (null === $runtimeOutcome && (0 !== $receiptCount || $capabilityResultPresent || $validatedProposalPresent || $cleanupCompletedWithoutFailure)) {
            throw InvalidAgentInspectionException::invalid('runtime_structure');
        }
        if (self::SCOPE !== $scope || self::DISCLOSURE !== $disclosure || self::PROFILE_CURRENTNESS !== $profileCurrentness
            || self::EXTERNAL_AUTHORITY_CURRENTNESS !== $externalAuthorityCurrentness || self::AUTHORITY !== $authority
            || self::APPLICATION_STATE !== $applicationState || self::PROVIDER_STATE !== $providerState
            || self::GRAPH_RELATIONSHIP !== $graphRelationship || self::TRUSTED_CODE_ISOLATION !== $trustedCodeIsolation) {
            throw InvalidAgentInspectionException::invalid('semantic_tokens');
        }
        $this->identity = hash(
            'sha256',
            CanonicalJson::encode(['schema' => self::SCHEMA, 'version' => self::VERSION] + $this->payload()),
        );
    }

    public function identity(): string
    {
        return $this->identity;
    }
    public function profileIdentity(): string
    {
        return $this->profileIdentity;
    }
    public function declarationArtifactSha256(): string
    {
        return $this->declarationArtifactSha256;
    }
    public function declarationIdentity(): string
    {
        return $this->declarationIdentity;
    }
    public function admissionIdentity(): string
    {
        return $this->admissionIdentity;
    }
    public function generatedWiringSha256(): string
    {
        return $this->generatedWiringSha256;
    }
    public function contractVersion(): ContractVersion
    {
        return $this->contractVersion;
    }
    public function declaredCapabilityCount(): int
    {
        return $this->declaredCapabilityCount;
    }
    public function requiredCapabilityCount(): int
    {
        return $this->requiredCapabilityCount;
    }
    public function optionalCapabilityCount(): int
    {
        return $this->optionalCapabilityCount;
    }
    public function permittedCapabilityCount(): int
    {
        return $this->permittedCapabilityCount;
    }
    public function deniedCapabilityCount(): int
    {
        return $this->deniedCapabilityCount;
    }
    public function constraintCount(): int
    {
        return $this->constraintCount;
    }
    public function evidenceReferenceCount(): int
    {
        return $this->evidenceReferenceCount;
    }
    public function declaredVisibility(): AgentVisibility
    {
        return $this->declaredVisibility;
    }
    public function runtimeEvidenceSupplied(): bool
    {
        return null !== $this->runtimeOutcome;
    }
    public function runtimeOutcome(): ?AgentRuntimeOutcome
    {
        return $this->runtimeOutcome;
    }
    public function runtimeTerminalState(): ?AgentRuntimeState
    {
        return $this->runtimeTerminalState;
    }
    public function runtimeUsage(): ?AgentRuntimeUsage
    {
        return $this->runtimeUsage;
    }
    public function receiptCount(): int
    {
        return $this->receiptCount;
    }
    public function capabilityResultPresent(): bool
    {
        return $this->capabilityResultPresent;
    }
    public function validatedProposalPresent(): bool
    {
        return $this->validatedProposalPresent;
    }
    public function cleanupCompletedWithoutFailure(): bool
    {
        return $this->cleanupCompletedWithoutFailure;
    }
    public function scope(): string
    {
        return $this->scope;
    }
    public function disclosure(): string
    {
        return $this->disclosure;
    }
    public function profileCurrentness(): string
    {
        return $this->profileCurrentness;
    }
    public function externalAuthorityCurrentness(): string
    {
        return $this->externalAuthorityCurrentness;
    }
    public function authority(): string
    {
        return $this->authority;
    }
    public function applicationState(): string
    {
        return $this->applicationState;
    }
    public function providerState(): string
    {
        return $this->providerState;
    }
    public function graphRelationship(): string
    {
        return $this->graphRelationship;
    }
    public function trustedCodeIsolation(): string
    {
        return $this->trustedCodeIsolation;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $payload = $this->payload();

        return ['schema' => self::SCHEMA, 'version' => self::VERSION, 'inspection_identity' => $this->identity] + $payload;
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        return [
            'profile_identity' => $this->profileIdentity,
            'declaration_artifact_sha256' => $this->declarationArtifactSha256,
            'declaration_identity' => $this->declarationIdentity,
            'admission_identity' => $this->admissionIdentity,
            'generated_wiring_sha256' => $this->generatedWiringSha256,
            'contract_version' => $this->contractVersion->value,
            'declared_capability_count' => $this->declaredCapabilityCount,
            'required_capability_count' => $this->requiredCapabilityCount,
            'optional_capability_count' => $this->optionalCapabilityCount,
            'permitted_capability_count' => $this->permittedCapabilityCount,
            'denied_capability_count' => $this->deniedCapabilityCount,
            'constraint_count' => $this->constraintCount,
            'evidence_reference_count' => $this->evidenceReferenceCount,
            'declared_visibility' => $this->declaredVisibility->value,
            'runtime_evidence_supplied' => $this->runtimeEvidenceSupplied(),
            'runtime_summary' => $this->runtimeSummary(),
            'scope' => $this->scope,
            'disclosure' => $this->disclosure,
            'profile_currentness' => $this->profileCurrentness,
            'external_authority_currentness' => $this->externalAuthorityCurrentness,
            'authority' => $this->authority,
            'application_state' => $this->applicationState,
            'provider_state' => $this->providerState,
            'graph_relationship' => $this->graphRelationship,
            'trusted_code_isolation' => $this->trustedCodeIsolation,
        ];
    }

    /** @return array<string, mixed> */
    private function runtimeSummary(): array
    {
        if (null === $this->runtimeOutcome || null === $this->runtimeTerminalState || null === $this->runtimeUsage) {
            return [];
        }

        return [
            'outcome' => $this->runtimeOutcome->value,
            'terminal_state' => $this->runtimeTerminalState->value,
            'usage' => [
                'steps' => $this->runtimeUsage->steps(),
                'duration_milliseconds' => $this->runtimeUsage->durationMilliseconds(),
                'tokens' => $this->runtimeUsage->tokens(),
                'capability_calls' => $this->runtimeUsage->capabilityCalls(),
                'retries' => $this->runtimeUsage->retries(),
                'delegation_depth' => $this->runtimeUsage->delegationDepth(),
                'output_bytes' => $this->runtimeUsage->outputBytes(),
                'retained_state_bytes' => $this->runtimeUsage->retainedStateBytes(),
                'concurrency' => $this->runtimeUsage->concurrency(),
            ],
            'receipt_count' => $this->receiptCount,
            'capability_result_present' => $this->capabilityResultPresent,
            'validated_proposal_present' => $this->validatedProposalPresent,
            'cleanup_completed_without_failure' => $this->cleanupCompletedWithoutFailure,
        ];
    }
}
