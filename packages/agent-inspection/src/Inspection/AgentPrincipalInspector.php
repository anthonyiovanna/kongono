<?php

declare(strict_types=1);

namespace Kongono\AgentInspection\Inspection;

use Kongono\AgentAdmission\Authority\CapabilityAuthorityStatus;
use Kongono\AgentApplication\Generated\AgentWiringValidator;
use Kongono\AgentInspection\Exception\AgentInspectionBindingException;
use Kongono\AgentInspection\Exception\InvalidCompletedRuntimeEvidenceException;
use Kongono\AgentPrincipalContract\Capability\CapabilityIdentity;
use Kongono\AgentPrincipalContract\Capability\CapabilityRequirement;
use Kongono\AgentPrincipalContract\Constraint\AgentConstraint;
use Kongono\AgentPrincipalContract\ContractVersion;
use Kongono\AgentPrincipalContract\Evidence\EvidenceReference;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Rebinds exact supplied evidence and projects only disclosure-safe aggregates. */
#[Api(ApiStatus::Experimental)]
final readonly class AgentPrincipalInspector
{
    public function inspect(AgentPrincipalInspectionRequest $request): AgentPrincipalInspection
    {
        $profile = $request->profile();
        $suppliedWiring = $request->wiring();
        try {
            $wiring = (new AgentWiringValidator())->validateCurrent($profile, $suppliedWiring->canonicalBytes());
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw AgentInspectionBindingException::mismatch('profile_wiring', $failure);
        }
        if (!$wiring->profileIdentity()->equals($suppliedWiring->profileIdentity())
            || !hash_equals($wiring->generatedWiringSha256(), $suppliedWiring->generatedWiringSha256())
            || !hash_equals($wiring->declarationArtifactSha256(), $suppliedWiring->declarationArtifactSha256())
            || !$wiring->declarationIdentity()->equals($suppliedWiring->declarationIdentity())
            || !$wiring->admissionIdentity()->equals($suppliedWiring->admissionIdentity())
            || !$this->sameCapabilities($wiring->permittedCapabilityIdentities(), $suppliedWiring->permittedCapabilityIdentities())
            || !hash_equals($wiring->implementationSha256(), $suppliedWiring->implementationSha256())
            || $wiring->generatedNamespace() !== $suppliedWiring->generatedNamespace()
            || $wiring->generatedClass() !== $suppliedWiring->generatedClass()) {
            throw AgentInspectionBindingException::mismatch('profile_wiring');
        }

        $declaration = $profile->declaration();
        $artifact = $profile->declarationArtifact();
        $plan = $profile->authorityPlan();
        if (!$profile->identity()->equals($wiring->profileIdentity())) {
            throw AgentInspectionBindingException::mismatch('profile_wiring');
        }
        if (!$declaration->identity()->equals($artifact->declarationIdentity())
            || !$declaration->identity()->equals($plan->declarationIdentity())
            || !$declaration->identity()->equals($wiring->declarationIdentity())) {
            throw AgentInspectionBindingException::mismatch('profile_declaration');
        }
        if (!hash_equals($artifact->artifactSha256(), $wiring->declarationArtifactSha256())
            || !hash_equals($artifact->declarationSha256(), $plan->declarationSha256())) {
            throw AgentInspectionBindingException::mismatch('profile_artifact');
        }
        if (!$plan->identity()->equals($wiring->admissionIdentity())) {
            throw AgentInspectionBindingException::mismatch('profile_plan');
        }

        $permitted = [];
        $denied = 0;
        foreach ($plan->capabilityDecisions() as $decision) {
            if ($decision->permitted()) {
                $permitted[] = $decision->capabilityIdentity();
            }
            if (CapabilityAuthorityStatus::Denied === $decision->status()) {
                ++$denied;
            }
        }
        if (!$this->sameCapabilities($profile->permittedCapabilityIdentities(), $permitted)
            || !$this->sameCapabilities($permitted, $wiring->permittedCapabilityIdentities())) {
            throw AgentInspectionBindingException::mismatch('capability_order');
        }
        if (!$this->sameConstraints($declaration->constraints(), $plan->constraints())) {
            throw AgentInspectionBindingException::mismatch('constraints');
        }
        if (!$this->sameEvidence($declaration->evidenceReferences(), $plan->evidenceReferences())) {
            throw AgentInspectionBindingException::mismatch('evidence_references');
        }

        $required = 0;
        $optional = 0;
        foreach ($declaration->capabilityRequests() as $capability) {
            CapabilityRequirement::Required === $capability->requirement() ? ++$required : ++$optional;
        }

        $result = $request->runtimeResult();
        if (null !== $result) {
            foreach ($result->receipts() as $receipt) {
                if (!$receipt->principal()->equals($declaration->principal())) {
                    throw InvalidCompletedRuntimeEvidenceException::invalid('receipt_principal');
                }
            }
        }

        return new AgentPrincipalInspection(
            $profile->identity()->value(),
            $artifact->artifactSha256(),
            $declaration->identity()->value(),
            $plan->identity()->value(),
            $wiring->generatedWiringSha256(),
            ContractVersion::Version1,
            count($declaration->capabilityRequests()),
            $required,
            $optional,
            count($permitted),
            $denied,
            count($declaration->constraints()),
            count($declaration->evidenceReferences()),
            $declaration->visibility(),
            $result?->outcome(),
            $result?->terminalState(),
            $result?->usage(),
            null === $result ? 0 : count($result->receipts()),
            null !== $result?->capabilityResult(),
            null !== $result?->validatedProposal(),
            $result?->cleanupCompletedWithoutFailure() ?? false,
        );
    }

    /**
     * @param list<CapabilityIdentity> $left
     * @param list<CapabilityIdentity> $right
     */
    private function sameCapabilities(array $left, array $right): bool
    {
        if (count($left) !== count($right)) {
            return false;
        }
        foreach ($left as $index => $capability) {
            if ($capability->value() !== $right[$index]->value()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param list<AgentConstraint> $left
     * @param list<AgentConstraint> $right
     */
    private function sameConstraints(array $left, array $right): bool
    {
        if (count($left) !== count($right)) {
            return false;
        }
        foreach ($left as $index => $constraint) {
            if ($constraint->kind() !== $right[$index]->kind() || $constraint->value() !== $right[$index]->value()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param list<EvidenceReference> $left
     * @param list<EvidenceReference> $right
     */
    private function sameEvidence(array $left, array $right): bool
    {
        if (count($left) !== count($right)) {
            return false;
        }
        foreach ($left as $index => $reference) {
            $other = $right[$index];
            if ($reference->kind() !== $other->kind() || $reference->identity()->value() !== $other->identity()->value() || !hash_equals($reference->sha256(), $other->sha256())) {
                return false;
            }
        }

        return true;
    }
}
