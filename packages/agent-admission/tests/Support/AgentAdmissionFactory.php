<?php

declare(strict_types=1);

namespace Kongono\AgentAdmission\Tests\Support;

use Kongono\AgentAdmission\Admission\AgentAdmissionRequest;
use Kongono\AgentAdmission\Evidence\CapabilityAuthorizationEvidence;
use Kongono\AgentDeclarationBuild\Artifact\AgentDeclarationArtifactBuilder;
use Kongono\AgentDeclarationBuild\Declaration\AgentDeclaration;
use Kongono\AgentDeclarationBuild\Declaration\AgentDeclarationIdentity;
use Kongono\AgentPrincipalContract\Capability\CapabilityIdentity;
use Kongono\AgentPrincipalContract\Capability\CapabilityPurpose;
use Kongono\AgentPrincipalContract\Capability\CapabilityRequest;
use Kongono\AgentPrincipalContract\Capability\CapabilityRequirement;
use Kongono\AgentPrincipalContract\Constraint\AgentConstraint;
use Kongono\AgentPrincipalContract\Constraint\ConstraintKind;
use Kongono\AgentPrincipalContract\Evidence\EvidenceIdentity;
use Kongono\AgentPrincipalContract\Evidence\EvidenceKind;
use Kongono\AgentPrincipalContract\Evidence\EvidenceReference;
use Kongono\AgentPrincipalContract\Identity\AgentPrincipalReference;
use Kongono\AgentPrincipalContract\Provenance\AgentProvenance;
use Kongono\AgentPrincipalContract\Provenance\ProvenanceSource;
use Kongono\AgentPrincipalContract\Visibility\AgentVisibility;
use Kongono\Authentication\AuthenticationFacts;
use Kongono\Authentication\AuthenticationInstant;
use Kongono\Authentication\AuthenticationMechanism;
use Kongono\Authentication\AuthenticationStrength;
use Kongono\Authentication\AuthenticationVerification;
use Kongono\Authorization\AuthorizationDecisionReceipt;
use Kongono\Authorization\AuthorizationEnvironmentEvidence;
use Kongono\Authorization\AuthorizationEnvironmentProfile;
use Kongono\Authorization\AuthorizationEvaluation;
use Kongono\Authorization\AuthorizationPolicyEvidence;
use Kongono\Authorization\AuthorizationPolicyName;
use Kongono\Authorization\AuthorizationPolicyProvenance;
use Kongono\Authorization\AuthorizationPolicyReference;
use Kongono\Authorization\AuthorizationPolicyVersion;
use Kongono\Authorization\AuthorizationRequest;
use Kongono\IdentityAccess\Action;
use Kongono\IdentityAccess\AuthorizationDecision;
use Kongono\IdentityAccess\PrincipalId;
use Kongono\IdentityAccess\PrincipalKind;
use Kongono\IdentityAccess\PrincipalReference;
use Kongono\IdentityAccess\ResourceId;
use Kongono\IdentityAccess\ResourceReference;
use Kongono\IdentityAccess\ResourceType;
use Kongono\IdentityAccess\SecurityContext;

final class AgentAdmissionFactory
{
    /** @param list<CapabilityRequest>|null $capabilityRequests */
    public static function declaration(?array $capabilityRequests = null, string $principalId = 'agent-primary'): AgentDeclaration
    {
        return new AgentDeclaration(
            new AgentDeclarationIdentity('assistant.primary'),
            new AgentPrincipalReference(self::principal($principalId)),
            $capabilityRequests ?? [self::capability('calendar.read', CapabilityRequirement::Required)],
            [new AgentConstraint(ConstraintKind::StepLimit, 4)],
            [new EvidenceReference(EvidenceKind::Policy, new EvidenceIdentity('policy.primary'), str_repeat('1', 64))],
            new AgentProvenance(ProvenanceSource::Application, 'application.primary', str_repeat('2', 64)),
            AgentVisibility::Protected,
        );
    }

    public static function capability(string $identity, CapabilityRequirement $requirement): CapabilityRequest
    {
        return new CapabilityRequest(
            new CapabilityIdentity($identity),
            new CapabilityPurpose('Bounded purpose for ' . $identity),
            $requirement,
        );
    }

    public static function principal(string $id = 'agent-primary'): PrincipalReference
    {
        return new PrincipalReference(PrincipalKind::Agent, new PrincipalId($id));
    }

    public static function authenticated(string $principalId = 'agent-primary'): AuthenticationVerification
    {
        return AuthenticationVerification::authenticated(new AuthenticationFacts(
            self::principal($principalId),
            new AuthenticationMechanism('fixture.v1'),
            new AuthenticationStrength(2),
            new AuthenticationInstant(100),
            new AuthenticationInstant(200),
        ));
    }

    public static function evidence(
        string $capability,
        AuthorizationDecision $decision = AuthorizationDecision::Permit,
        string $policyVersion = 'v1',
    ): CapabilityAuthorizationEvidence {
        $policy = new AuthorizationPolicyReference(
            new AuthorizationPolicyName('agent.admission'),
            new AuthorizationPolicyVersion($policyVersion),
            new AuthorizationPolicyProvenance('fixture.primary'),
        );
        $policyEvidence = new class ($policy) implements AuthorizationPolicyEvidence {
            public function __construct(private AuthorizationPolicyReference $reference) {}
            public function reference(): AuthorizationPolicyReference
            {
                return $this->reference;
            }
        };
        $environment = new class implements AuthorizationEnvironmentEvidence {
            public function profile(): AuthorizationEnvironmentProfile
            {
                return new AuthorizationEnvironmentProfile('fixture.v1');
            }
        };
        $request = new AuthorizationRequest(
            SecurityContext::authenticated(self::principal()),
            new Action('agent.capability.use'),
            new ResourceReference(new ResourceType('agent.capability'), new ResourceId('fixture-resource')),
            $policy,
            $policyEvidence,
            $environment,
        );

        return new CapabilityAuthorizationEvidence(
            new CapabilityIdentity($capability),
            new AuthorizationEvaluation(AuthorizationDecisionReceipt::evaluated($request, $decision)),
        );
    }

    /**
     * @param list<CapabilityIdentity> $requested
     * @param list<CapabilityAuthorizationEvidence> $evidence
     */
    public static function request(
        ?AgentDeclaration $declaration = null,
        ?AuthenticationVerification $verification = null,
        array $requested = [],
        array $evidence = [],
    ): AgentAdmissionRequest {
        $declaration ??= self::declaration();

        return new AgentAdmissionRequest(
            $declaration,
            (new AgentDeclarationArtifactBuilder())->build($declaration)->canonicalBytes(),
            $verification ?? self::authenticated(),
            $requested,
            $evidence,
        );
    }

    private function __construct() {}
}
