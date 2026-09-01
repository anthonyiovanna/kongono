<?php

declare(strict_types=1);

namespace Kongono\AgentApplication\Profile;

use Kongono\AgentAdmission\Admission\AgentAdmissionDecision;
use Kongono\AgentAdmission\Authority\AgentAuthorityPlan;
use Kongono\AgentApplication\Exception\InvalidAgentApplicationProfileException;
use Kongono\AgentApplication\Internal\CanonicalProfile;
use Kongono\AgentDeclarationBuild\Artifact\AgentDeclarationArtifactValidator;
use Kongono\AgentDeclarationBuild\Declaration\AgentDeclaration;
use Kongono\AgentPrincipalContract\Capability\CapabilityRequirement;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Validates and binds one exact current declaration artifact and admitted plan. */
#[Api(ApiStatus::Experimental)]
final class AgentApplicationProfileFactory
{
    public function create(AgentDeclaration $declaration, string $artifactBytes, AgentAuthorityPlan $plan): AgentApplicationProfile
    {
        try {
            $artifact = (new AgentDeclarationArtifactValidator())->validateCurrent($declaration, $artifactBytes);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw InvalidAgentApplicationProfileException::invalid('declaration_artifact_mismatch', previous: $failure);
        }
        if (AgentAdmissionDecision::Admitted !== $plan->admissionDecision()) {
            throw InvalidAgentApplicationProfileException::invalid('admission_denied');
        }
        if (!$plan->declarationIdentity()->equals($declaration->identity()) || !hash_equals($plan->declarationSha256(), $artifact->declarationSha256())) {
            throw InvalidAgentApplicationProfileException::invalid('declaration_plan_mismatch');
        }
        if (!$plan->principal()->reference()->equals($declaration->principal()->reference())) {
            throw InvalidAgentApplicationProfileException::invalid('principal_mismatch');
        }
        self::constraints($declaration, $plan);
        self::evidence($declaration, $plan);
        $permitted = self::capabilities($declaration, $plan);
        $identity = new AgentApplicationProfileIdentity(hash('sha256', CanonicalProfile::bytes($declaration, $artifact, $plan)));

        return new AgentApplicationProfile($declaration, $artifact, $plan, $identity, $permitted);
    }

    /** @return list<\Kongono\AgentPrincipalContract\Capability\CapabilityIdentity> */
    private static function capabilities(AgentDeclaration $declaration, AgentAuthorityPlan $plan): array
    {
        $requests = $declaration->capabilityRequests();
        $decisions = $plan->capabilityDecisions();
        if (count($requests) !== count($decisions)) {
            throw InvalidAgentApplicationProfileException::invalid('capability_count_mismatch');
        }
        $permitted = [];
        foreach ($requests as $position => $request) {
            $decision = $decisions[$position];
            if (
                !$decision->capabilityIdentity()->equals($request->identity())
                || $decision->declaredRequirement() !== $request->requirement()
            ) {
                throw InvalidAgentApplicationProfileException::invalid('capability_order_or_identity_mismatch', $position);
            }
            if (CapabilityRequirement::Required === $request->requirement() && !$decision->permitted()) {
                throw InvalidAgentApplicationProfileException::invalid('required_capability_not_permitted', $position);
            }
            if ($decision->permitted()) {
                $permitted[] = $request->identity();
            }
        }

        return $permitted;
    }

    private static function constraints(AgentDeclaration $declaration, AgentAuthorityPlan $plan): void
    {
        $expected = $declaration->constraints();
        $actual = $plan->constraints();
        if (count($expected) !== count($actual)) {
            throw InvalidAgentApplicationProfileException::invalid('constraint_count_mismatch');
        }
        foreach ($expected as $position => $constraint) {
            if ($constraint->kind() !== $actual[$position]->kind() || $constraint->value() !== $actual[$position]->value()) {
                throw InvalidAgentApplicationProfileException::invalid('constraint_mismatch', $position);
            }
        }
    }

    private static function evidence(AgentDeclaration $declaration, AgentAuthorityPlan $plan): void
    {
        $expected = $declaration->evidenceReferences();
        $actual = $plan->evidenceReferences();
        if (count($expected) !== count($actual)) {
            throw InvalidAgentApplicationProfileException::invalid('evidence_count_mismatch');
        }
        foreach ($expected as $position => $evidence) {
            $candidate = $actual[$position];
            if ($evidence->kind() !== $candidate->kind() || !$evidence->identity()->equals($candidate->identity()) || !hash_equals($evidence->sha256(), $candidate->sha256())) {
                throw InvalidAgentApplicationProfileException::invalid('evidence_mismatch', $position);
            }
        }
    }
}
