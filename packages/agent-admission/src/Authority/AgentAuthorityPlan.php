<?php

declare(strict_types=1);

namespace Kongono\AgentAdmission\Authority;

use Kongono\AgentAdmission\Admission\AgentAdmissionDecision;
use Kongono\AgentAdmission\Identity\AgentAdmissionIdentity;
use Kongono\AgentAdmission\Internal\AdmissionGuard;
use Kongono\AgentDeclarationBuild\Declaration\AgentDeclarationIdentity;
use Kongono\AgentPrincipalContract\Constraint\AgentConstraint;
use Kongono\AgentPrincipalContract\Evidence\EvidenceReference;
use Kongono\AgentPrincipalContract\Identity\AgentPrincipalReference;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\IdentityAccess\AuthenticationOutcomeKind;

/** Stores immutable canonical admission evidence without executable authority. */
#[Api(ApiStatus::Experimental)]
final readonly class AgentAuthorityPlan
{
    /** @var list<CapabilityAuthorityDecision> */
    private array $capabilityDecisions;
    /** @var list<AgentConstraint> */
    private array $constraints;
    /** @var list<EvidenceReference> */
    private array $evidenceReferences;

    /**
     * @param array<array-key, mixed> $capabilityDecisions
     * @param array<array-key, mixed> $constraints
     * @param array<array-key, mixed> $evidenceReferences
     */
    public function __construct(
        private AgentAdmissionIdentity $identity,
        private AgentDeclarationIdentity $declarationIdentity,
        string $declarationSha256,
        private AgentPrincipalReference $principal,
        private AuthenticationOutcomeKind $authenticationOutcome,
        private AgentAdmissionDecision $admissionDecision,
        array $capabilityDecisions,
        array $constraints,
        array $evidenceReferences,
    ) {
        $this->declarationSha256 = AdmissionGuard::declarationDigest($declarationSha256);
        $this->capabilityDecisions = AdmissionGuard::capabilityDecisions($capabilityDecisions);
        $this->constraints = AdmissionGuard::constraints($constraints);
        $this->evidenceReferences = AdmissionGuard::evidenceReferences($evidenceReferences);
    }

    private string $declarationSha256;

    public function identity(): AgentAdmissionIdentity
    {
        return $this->identity;
    }

    public function declarationIdentity(): AgentDeclarationIdentity
    {
        return $this->declarationIdentity;
    }

    public function declarationSha256(): string
    {
        return $this->declarationSha256;
    }

    public function principal(): AgentPrincipalReference
    {
        return $this->principal;
    }

    public function authenticationOutcome(): AuthenticationOutcomeKind
    {
        return $this->authenticationOutcome;
    }

    public function admissionDecision(): AgentAdmissionDecision
    {
        return $this->admissionDecision;
    }

    /** @return list<CapabilityAuthorityDecision> */
    public function capabilityDecisions(): array
    {
        return [...$this->capabilityDecisions];
    }

    /** @return list<AgentConstraint> */
    public function constraints(): array
    {
        return [...$this->constraints];
    }

    /** @return list<EvidenceReference> */
    public function evidenceReferences(): array
    {
        return [...$this->evidenceReferences];
    }
}
