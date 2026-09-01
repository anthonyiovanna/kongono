<?php

declare(strict_types=1);

namespace Kongono\AgentAdmission\Admission;

use Kongono\AgentAdmission\Authority\AgentAuthorityPlan;
use Kongono\AgentAdmission\Authority\CapabilityAuthorityDecision;
use Kongono\AgentAdmission\Authority\CapabilityAuthorityStatus;
use Kongono\AgentAdmission\Exception\InvalidAgentAdmissionException;
use Kongono\AgentAdmission\Internal\CanonicalAdmission;
use Kongono\AgentDeclarationBuild\Artifact\AgentDeclarationArtifactValidator;
use Kongono\AgentPrincipalContract\Capability\CapabilityRequirement;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\IdentityAccess\AuthenticationOutcomeKind;
use Kongono\IdentityAccess\AuthorizationDecision;
use Throwable;

/** Reconciles explicit completed evidence into one inert authority plan. */
#[Api(ApiStatus::Experimental)]
final class AgentAdmissionBuilder
{
    public function build(AgentAdmissionRequest $request): AgentAuthorityPlan
    {
        try {
            $artifact = (new AgentDeclarationArtifactValidator())->validateCurrent(
                $request->declaration(),
                $request->declarationArtifactBytes(),
            );
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw InvalidAgentAdmissionException::artifact(strlen($request->declarationArtifactBytes()), $failure);
        }

        $requested = [];
        foreach ($request->requestedCapabilities() as $capability) {
            $requested[$capability->value()] = $capability;
        }
        $evidence = [];
        foreach ($request->authorizationEvidence() as $entry) {
            $evidence[$entry->capabilityIdentity()->value()] = $entry;
        }

        $decisions = [];
        $declared = [];
        $allRequestedPermitted = true;
        $requiredPermitted = true;
        foreach ($request->declaration()->capabilityRequests() as $declaredRequest) {
            $key = $declaredRequest->identity()->value();
            $declared[$key] = true;
            $authorization = $evidence[$key] ?? null;
            if (!isset($requested[$key])) {
                $status = CapabilityAuthorityStatus::NotRequested;
                $receipt = null;
            } elseif (null === $authorization) {
                $status = CapabilityAuthorityStatus::MissingAuthorization;
                $receipt = null;
                $allRequestedPermitted = false;
            } else {
                $receipt = $authorization->evaluation()->receipt();
                $status = match ($authorization->evaluation()->decision()) {
                    AuthorizationDecision::Permit => CapabilityAuthorityStatus::Permitted,
                    AuthorizationDecision::Deny => CapabilityAuthorityStatus::Denied,
                    AuthorizationDecision::Indeterminate => CapabilityAuthorityStatus::Indeterminate,
                };
                if (CapabilityAuthorityStatus::Permitted !== $status) {
                    $allRequestedPermitted = false;
                }
            }

            $decision = new CapabilityAuthorityDecision(
                $declaredRequest->identity(),
                $declaredRequest->requirement(),
                $status,
                $receipt,
            );
            $decisions[] = $decision;
            if (CapabilityRequirement::Required === $declaredRequest->requirement() && !$decision->permitted()) {
                $requiredPermitted = false;
            }
        }

        foreach ($request->requestedCapabilities() as $capability) {
            $key = $capability->value();
            if (isset($declared[$key])) {
                continue;
            }
            $authorization = $evidence[$key] ?? null;
            $decisions[] = new CapabilityAuthorityDecision(
                $capability,
                null,
                CapabilityAuthorityStatus::Undeclared,
                $authorization?->evaluation()->receipt(),
            );
            $allRequestedPermitted = false;
        }

        $verification = $request->authenticationVerification();
        $facts = $verification->facts();
        $authenticationMatches = AuthenticationOutcomeKind::Authenticated === $verification->outcome()->kind()
            && null !== $facts
            && $request->declaration()->principal()->reference()->equals($facts->principal())
            && null !== $verification->outcome()->principal()
            && $request->declaration()->principal()->reference()->equals($verification->outcome()->principal());
        $admissionDecision = $authenticationMatches && $allRequestedPermitted && $requiredPermitted
            ? AgentAdmissionDecision::Admitted
            : AgentAdmissionDecision::Denied;
        $identity = CanonicalAdmission::identity($request, $artifact, $admissionDecision, $decisions);

        return new AgentAuthorityPlan(
            $identity,
            $artifact->declarationIdentity(),
            $artifact->declarationSha256(),
            $request->declaration()->principal(),
            $verification->outcome()->kind(),
            $admissionDecision,
            $decisions,
            $request->declaration()->constraints(),
            $request->declaration()->evidenceReferences(),
        );
    }
}
