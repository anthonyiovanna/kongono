<?php

declare(strict_types=1);

namespace Kongono\AgentPrincipalContract\Tests\Unit\Proposal;

use Kongono\AgentPrincipalContract\Capability\CapabilityIdentity;
use Kongono\AgentPrincipalContract\Capability\CapabilityPurpose;
use Kongono\AgentPrincipalContract\Capability\CapabilityRequest;
use Kongono\AgentPrincipalContract\Capability\CapabilityRequirement;
use Kongono\AgentPrincipalContract\Constraint\AgentConstraint;
use Kongono\AgentPrincipalContract\Constraint\ConstraintKind;
use Kongono\AgentPrincipalContract\Evidence\EvidenceIdentity;
use Kongono\AgentPrincipalContract\Evidence\EvidenceKind;
use Kongono\AgentPrincipalContract\Evidence\EvidenceReference;
use Kongono\AgentPrincipalContract\Exception\InvalidAgentPrincipalContractException;
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
use Kongono\IdentityAccess\PrincipalId;
use Kongono\IdentityAccess\PrincipalKind;
use Kongono\IdentityAccess\PrincipalReference;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class ActionProposalTest extends TestCase
{
    public function testZeroAndMaximumCanonicalListsAreAcceptedAndDetached(): void
    {
        self::assertSame([], self::proposal([], [])->constraints());
        self::assertSame([], self::proposal([], [])->evidenceReferences());

        $constraints = array_map(static fn(ConstraintKind $kind): AgentConstraint => new AgentConstraint($kind, 1), ConstraintKind::cases());
        usort($constraints, static fn(AgentConstraint $left, AgentConstraint $right): int => strcmp($left->kind()->value, $right->kind()->value));
        $evidence = [];
        for ($index = 0; $index < 32; ++$index) {
            $evidence[] = self::evidence(EvidenceKind::Authentication, sprintf('evidence%02d', $index));
        }

        $proposal = self::proposal($constraints, $evidence);
        $constraints[] = new AgentConstraint(ConstraintKind::StepLimit, 2);
        $evidence = [];
        $returnedConstraints = $proposal->constraints();
        $returnedEvidence = $proposal->evidenceReferences();
        array_pop($returnedConstraints);
        array_pop($returnedEvidence);

        self::assertCount(9, $proposal->constraints());
        self::assertCount(32, $proposal->evidenceReferences());
        self::assertNotSame($returnedConstraints, $proposal->constraints());
        self::assertNotSame($returnedEvidence, $proposal->evidenceReferences());
    }

    public function testEveryMalformedListShapeTypeDuplicateOrderAndLimitFailsClosed(): void
    {
        $wrong = 'super-secret-wrong-entry';
        $cases = [
            fn(): ActionProposal => self::proposal(array_fill(0, 10, new AgentConstraint(ConstraintKind::StepLimit, 1)), []),
            fn(): ActionProposal => self::proposal([], array_fill(0, 33, self::evidence(EvidenceKind::Authentication, 'same'))),
            fn(): ActionProposal => self::proposal(['named' => new AgentConstraint(ConstraintKind::StepLimit, 1)], []),
            fn(): ActionProposal => self::proposal([], ['named' => self::evidence(EvidenceKind::Authentication, 'a')]),
            fn(): ActionProposal => self::proposal([$wrong], []),
            fn(): ActionProposal => self::proposal([], [$wrong]),
            fn(): ActionProposal => self::proposal([new AgentConstraint(ConstraintKind::RetryLimit, 1), new AgentConstraint(ConstraintKind::RetryLimit, 2)], []),
            fn(): ActionProposal => self::proposal([new AgentConstraint(ConstraintKind::StepLimit, 1), new AgentConstraint(ConstraintKind::RetryLimit, 1)], []),
            fn(): ActionProposal => self::proposal([], [self::evidence(EvidenceKind::Consent, 'a'), self::evidence(EvidenceKind::Consent, 'a')]),
            fn(): ActionProposal => self::proposal([], [self::evidence(EvidenceKind::Policy, 'z'), self::evidence(EvidenceKind::Authentication, 'a')]),
        ];

        foreach ($cases as $case) {
            try {
                $case();
                self::fail('A malformed proposal was accepted.');
            } catch (InvalidAgentPrincipalContractException $exception) {
                self::assertContains($exception->diagnosticId(), ['KGN-AGENT-CONTRACT-1305', 'KGN-AGENT-CONTRACT-1306']);
                self::assertStringNotContainsString($wrong, $exception->getMessage());
                self::assertStringNotContainsString($wrong, (string) $exception);
                self::assertStringNotContainsString($wrong, self::contextText($exception));
            }
        }
    }

    public function testAccessorsExposeOnlyExactInertInputs(): void
    {
        $constraint = new AgentConstraint(ConstraintKind::RetryLimit, 2);
        $evidence = self::evidence(EvidenceKind::Consent, 'approval');
        $proposal = self::proposal([$constraint], [$evidence]);
        self::assertSame('proposal.one', $proposal->identity()->value());
        self::assertSame(PrincipalKind::Agent, $proposal->principal()->reference()->kind());
        self::assertSame('operation.one', $proposal->operation()->value());
        self::assertSame('intent.one', $proposal->intent()->identity()->value());
        self::assertSame('document.read', $proposal->capabilityRequest()->identity()->value());
        self::assertSame($constraint, $proposal->constraints()[0]);
        self::assertSame($evidence, $proposal->evidenceReferences()[0]);
        self::assertSame(ProvenanceSource::Caller, $proposal->provenance()->source());
        self::assertSame(AgentVisibility::Protected, $proposal->visibility());
    }

    /**
     * @param array<array-key, AgentConstraint|string> $constraints
     * @param array<array-key, EvidenceReference|string> $evidence
     */
    private static function proposal(array $constraints, array $evidence): ActionProposal
    {
        $proposal = (new ReflectionClass(ActionProposal::class))->newInstanceArgs([
            new ProposalIdentity('proposal.one'),
            new AgentPrincipalReference(new PrincipalReference(PrincipalKind::Agent, new PrincipalId('agent-1'))),
            new OperationReference('operation.one'),
            new AgentIntent(new IntentIdentity('intent.one'), new IntentPurpose('Perform one bounded review.')),
            new CapabilityRequest(new CapabilityIdentity('document.read'), new CapabilityPurpose('Read one selected document.'), CapabilityRequirement::Required),
            $constraints,
            $evidence,
            new AgentProvenance(ProvenanceSource::Caller, 'caller.input', str_repeat('a', 64)),
            AgentVisibility::Protected,
        ]);
        self::assertInstanceOf(ActionProposal::class, $proposal);

        return $proposal;
    }

    private static function evidence(EvidenceKind $kind, string $identity): EvidenceReference
    {
        return new EvidenceReference($kind, new EvidenceIdentity($identity), str_repeat('b', 64));
    }

    private static function contextText(InvalidAgentPrincipalContractException $exception): string
    {
        return implode('|', array_map(static fn($field): string => $field->key() . '=' . $field->value(), $exception->diagnosticContext()->fields()));
    }
}
