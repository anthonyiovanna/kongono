<?php

declare(strict_types=1);

namespace Kongono\AgentRuntime\Tests\Support;

use Kongono\AgentAdmission\Admission\AgentAdmissionBuilder;
use Kongono\AgentAdmission\Admission\AgentAdmissionRequest;
use Kongono\AgentAdmission\Authority\AgentAuthorityPlan;
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
use Kongono\AgentPrincipalContract\Identity\OperationReference;
use Kongono\AgentPrincipalContract\Intent\AgentIntent;
use Kongono\AgentPrincipalContract\Intent\IntentIdentity;
use Kongono\AgentPrincipalContract\Intent\IntentPurpose;
use Kongono\AgentPrincipalContract\Proposal\ActionProposal;
use Kongono\AgentPrincipalContract\Proposal\ProposalIdentity;
use Kongono\AgentPrincipalContract\Provenance\AgentProvenance;
use Kongono\AgentPrincipalContract\Provenance\ProvenanceSource;
use Kongono\AgentPrincipalContract\Visibility\AgentVisibility;
use Kongono\AgentRuntime\Budget\AgentRuntimeBudget;
use Kongono\AgentRuntime\Capability\AgentCapabilityOutcome;
use Kongono\AgentRuntime\Capability\AgentCapabilityResult;
use Kongono\AgentRuntime\Proposal\AgentProposalResult;
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

final class AgentRuntimeFactory
{
    /**
     * @param list<CapabilityRequest>|null $capabilities
     * @param list<AgentConstraint>|null $constraints
     */
    public static function declaration(?array $capabilities = null, ?array $constraints = null, string $principalId = 'agent-primary'): AgentDeclaration
    {
        return new AgentDeclaration(
            new AgentDeclarationIdentity('assistant.primary'),
            new AgentPrincipalReference(self::principal($principalId)),
            $capabilities ?? [self::capability('calendar.read')],
            $constraints ?? self::constraints(),
            [new EvidenceReference(EvidenceKind::Policy, new EvidenceIdentity('policy.primary'), str_repeat('1', 64))],
            new AgentProvenance(ProvenanceSource::Application, 'application.primary', str_repeat('2', 64)),
            AgentVisibility::Protected,
        );
    }

    /** @return list<AgentConstraint> */
    public static function constraints(): array
    {
        return [
            new AgentConstraint(ConstraintKind::CapabilityCallLimit, 1),
            new AgentConstraint(ConstraintKind::ConcurrencyLimit, 1),
            new AgentConstraint(ConstraintKind::DelegationDepthLimit, 0),
            new AgentConstraint(ConstraintKind::DurationMillisecondsLimit, 100),
            new AgentConstraint(ConstraintKind::OutputBytesLimit, 100),
            new AgentConstraint(ConstraintKind::RetainedStateBytesLimit, 0),
            new AgentConstraint(ConstraintKind::RetryLimit, 0),
            new AgentConstraint(ConstraintKind::StepLimit, 2),
            new AgentConstraint(ConstraintKind::TokenLimit, 100),
        ];
    }

    public static function capability(string $identity, CapabilityRequirement $requirement = CapabilityRequirement::Required): CapabilityRequest
    {
        return new CapabilityRequest(new CapabilityIdentity($identity), new CapabilityPurpose('Bounded purpose for ' . $identity), $requirement);
    }

    public static function operation(): OperationReference
    {
        return new OperationReference('operation.primary');
    }
    public static function intent(): AgentIntent
    {
        return new AgentIntent(new IntentIdentity('intent.primary'), new IntentPurpose('Perform one bounded operation'));
    }

    public static function plan(?AgentDeclaration $declaration = null): AgentAuthorityPlan
    {
        $declaration ??= self::declaration();
        $requested = [];
        $evidence = [];
        foreach ($declaration->capabilityRequests() as $capability) {
            $requested[] = $capability->identity();
            $evidence[] = self::authorizationEvidence($capability->identity()->value());
        }
        $artifact = (new AgentDeclarationArtifactBuilder())->build($declaration);
        return (new AgentAdmissionBuilder())->build(new AgentAdmissionRequest(
            $declaration,
            $artifact->canonicalBytes(),
            self::authenticated(),
            $requested,
            $evidence,
        ));
    }

    public static function proposal(?AgentDeclaration $declaration = null, ?OperationReference $operation = null, ?AgentIntent $intent = null, int $capabilityIndex = 0): ActionProposal
    {
        $declaration ??= self::declaration();
        return new ActionProposal(
            new ProposalIdentity('proposal.primary'),
            $declaration->principal(),
            $operation ?? self::operation(),
            $intent ?? self::intent(),
            $declaration->capabilityRequests()[$capabilityIndex],
            $declaration->constraints(),
            $declaration->evidenceReferences(),
            $declaration->provenance(),
            $declaration->visibility(),
        );
    }

    public static function proposalResult(?ActionProposal $proposal = null, int $tokens = 5, int $outputBytes = 3): AgentProposalResult
    {
        return new AgentProposalResult($proposal ?? self::proposal(), $tokens, $outputBytes, str_repeat('a', 64));
    }

    public static function capabilityResult(AgentCapabilityOutcome $outcome = AgentCapabilityOutcome::Performed, string $output = 'ok'): AgentCapabilityResult
    {
        return new AgentCapabilityResult($outcome, $output, str_repeat('b', 64));
    }

    /** @param array<string, int> $overrides */
    public static function budget(array $overrides = []): AgentRuntimeBudget
    {
        $values = array_replace([
            'steps' => 2, 'duration' => 100, 'tokens' => 100, 'capability_calls' => 1,
            'retries' => 0, 'delegation' => 0, 'output' => 100, 'retained' => 0, 'concurrency' => 1,
        ], $overrides);
        return new AgentRuntimeBudget($values['steps'], $values['duration'], $values['tokens'], $values['capability_calls'], $values['retries'], $values['delegation'], $values['output'], $values['retained'], $values['concurrency']);
    }

    private static function principal(string $id): PrincipalReference
    {
        return new PrincipalReference(PrincipalKind::Agent, new PrincipalId($id));
    }

    private static function authenticated(): AuthenticationVerification
    {
        return AuthenticationVerification::authenticated(new AuthenticationFacts(
            self::principal('agent-primary'),
            new AuthenticationMechanism('fixture.v1'),
            new AuthenticationStrength(2),
            new AuthenticationInstant(100),
            new AuthenticationInstant(200),
        ));
    }

    private static function authorizationEvidence(string $capability): CapabilityAuthorizationEvidence
    {
        $policy = new AuthorizationPolicyReference(new AuthorizationPolicyName('agent.runtime'), new AuthorizationPolicyVersion('v1'), new AuthorizationPolicyProvenance('fixture.primary'));
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
            SecurityContext::authenticated(self::principal('agent-primary')),
            new Action('agent.capability.use'),
            new ResourceReference(new ResourceType('agent.capability'), new ResourceId('fixture-resource')),
            $policy,
            $policyEvidence,
            $environment,
        );
        return new CapabilityAuthorizationEvidence(new CapabilityIdentity($capability), new AuthorizationEvaluation(AuthorizationDecisionReceipt::evaluated($request, AuthorizationDecision::Permit)));
    }

    private function __construct() {}
}
