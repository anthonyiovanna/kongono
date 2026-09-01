<?php

declare(strict_types=1);

namespace Kongono\AgentAdmission\Internal;

use JsonException;
use Kongono\AgentAdmission\Admission\AgentAdmissionDecision;
use Kongono\AgentAdmission\Admission\AgentAdmissionRequest;
use Kongono\AgentAdmission\Authority\CapabilityAuthorityDecision;
use Kongono\AgentAdmission\Evidence\CapabilityAuthorizationEvidence;
use Kongono\AgentAdmission\Exception\InvalidAgentAdmissionException;
use Kongono\AgentAdmission\Identity\AgentAdmissionIdentity;
use Kongono\AgentDeclarationBuild\Artifact\AgentDeclarationArtifact;
use Kongono\AgentPrincipalContract\Constraint\AgentConstraint;
use Kongono\AgentPrincipalContract\Evidence\EvidenceReference;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Authentication\AuthenticationVerification;
use Kongono\Authorization\AuthorizationDecisionReceipt;

/** Derives one exact versioned ambient-free authority-plan identity document. */
#[Api(ApiStatus::Internal)]
final class CanonicalAdmission
{
    /** @param list<CapabilityAuthorityDecision> $decisions */
    public static function identity(
        AgentAdmissionRequest $request,
        AgentDeclarationArtifact $artifact,
        AgentAdmissionDecision $admissionDecision,
        array $decisions,
    ): AgentAdmissionIdentity {
        $declaration = $request->declaration();
        $document = [
            'schema' => 'kongono.agent-authority-plan',
            'version' => 1,
            'contract_version' => 1,
            'declaration_identity' => $artifact->declarationIdentity()->value(),
            'declaration_sha256' => $artifact->declarationSha256(),
            'principal' => [
                'kind' => $declaration->principal()->reference()->kind()->value,
                'id' => $declaration->principal()->reference()->id()->value(),
            ],
            'authentication' => self::authentication($request->authenticationVerification()),
            'requested_capabilities' => array_map(static fn($capability): string => $capability->value(), $request->requestedCapabilities()),
            'authorization_evidence' => array_map(self::authorizationEvidence(...), $request->authorizationEvidence()),
            'admission_decision' => $admissionDecision->value,
            'capability_decisions' => array_map(self::capabilityDecision(...), $decisions),
            'constraints' => array_map(self::constraint(...), $declaration->constraints()),
            'evidence_references' => array_map(self::evidenceReference(...), $declaration->evidenceReferences()),
        ];

        try {
            $bytes = json_encode(
                $document,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION,
            );
        } catch (JsonException $failure) {
            throw InvalidAgentAdmissionException::canonical($failure);
        }

        return new AgentAdmissionIdentity(hash('sha256', $bytes));
    }

    /** @return array<string, mixed> */
    private static function authentication(AuthenticationVerification $verification): array
    {
        $facts = $verification->facts();

        return [
            'outcome' => $verification->outcome()->kind()->value,
            'facts' => null === $facts ? null : [
                'mechanism' => $facts->mechanism()->value(),
                'strength' => $facts->strength()->level(),
                'authenticated_at_second' => $facts->authenticatedAt()->seconds(),
                'valid_until_second' => $facts->validUntil()->seconds(),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function authorizationEvidence(CapabilityAuthorizationEvidence $evidence): array
    {
        return [
            'capability_identity' => $evidence->capabilityIdentity()->value(),
            'receipt' => self::receipt($evidence->evaluation()->receipt()),
        ];
    }

    /** @return array<string, mixed> */
    private static function capabilityDecision(CapabilityAuthorityDecision $decision): array
    {
        return [
            'capability_identity' => $decision->capabilityIdentity()->value(),
            'declared_requirement' => $decision->declaredRequirement()?->value,
            'status' => $decision->status()->value,
            'authorization_receipt' => null === $decision->authorizationReceipt()
                ? null
                : self::receipt($decision->authorizationReceipt()),
        ];
    }

    /** @return array<string, string> */
    private static function receipt(AuthorizationDecisionReceipt $receipt): array
    {
        return [
            'decision' => $receipt->decision()->value,
            'reason' => $receipt->reason()->value,
            'policy_name' => $receipt->policy()->name()->value(),
            'policy_version' => $receipt->policy()->version()->value(),
            'policy_provenance' => $receipt->policy()->provenance()->value(),
            'action' => $receipt->action()->value(),
            'resource_type' => $receipt->resourceType()->value(),
            'security_context_state' => $receipt->securityContextState()->value,
            'environment_profile' => $receipt->environmentProfile()->value(),
        ];
    }

    /** @return array{kind:string,value:int} */
    private static function constraint(AgentConstraint $constraint): array
    {
        return ['kind' => $constraint->kind()->value, 'value' => $constraint->value()];
    }

    /** @return array{kind:string,identity:string,sha256:string} */
    private static function evidenceReference(EvidenceReference $reference): array
    {
        return [
            'kind' => $reference->kind()->value,
            'identity' => $reference->identity()->value(),
            'sha256' => $reference->sha256(),
        ];
    }

    private function __construct() {}
}
