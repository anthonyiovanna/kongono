<?php

declare(strict_types=1);

namespace Kongono\AgentInspection\Tests\Unit\Inspection;

use Kongono\AgentAdmission\Authority\AgentAuthorityPlan;
use Kongono\AgentAdmission\Identity\AgentAdmissionIdentity;
use Kongono\AgentApplication\Generated\GeneratedAgentWiring;
use Kongono\AgentApplication\Profile\AgentApplicationProfile;
use Kongono\AgentApplication\Profile\AgentApplicationProfileIdentity;
use Kongono\AgentDeclarationBuild\Declaration\AgentDeclarationIdentity;
use Kongono\AgentInspection\Exception\AgentInspectionBindingException;
use Kongono\AgentInspection\Exception\InvalidCompletedRuntimeEvidenceException;
use Kongono\AgentInspection\Inspection\AgentPrincipalInspection;
use Kongono\AgentInspection\Inspection\AgentPrincipalInspectionRequest;
use Kongono\AgentInspection\Inspection\AgentPrincipalInspector;
use Kongono\AgentInspection\Tests\Support\AgentInspectionFactory;
use Kongono\AgentPrincipalContract\Constraint\AgentConstraint;
use Kongono\AgentPrincipalContract\Evidence\EvidenceReference;
use Kongono\AgentPrincipalContract\Identity\AgentPrincipalReference;
use Kongono\AgentPrincipalContract\Receipt\AgentReceipt;
use Kongono\AgentPrincipalContract\Receipt\ReceiptIdentity;
use Kongono\AgentPrincipalContract\Receipt\ReceiptOutcome;
use Kongono\AgentRuntime\Runtime\AgentRuntimeResult;
use Kongono\IdentityAccess\PrincipalId;
use Kongono\IdentityAccess\PrincipalKind;
use Kongono\IdentityAccess\PrincipalReference;
use PHPUnit\Framework\TestCase;

final class AgentPrincipalInspectorTest extends TestCase
{
    public function testExactProfileAndWiringProduceDeterministicBoundedInspection(): void
    {
        $first = AgentInspectionFactory::inspection();
        $second = AgentInspectionFactory::inspection();

        self::assertSame($first->toArray(), $second->toArray());
        self::assertSame(AgentPrincipalInspection::SCHEMA, $first->toArray()['schema']);
        self::assertSame(2, $first->declaredCapabilityCount());
        self::assertSame(1, $first->requiredCapabilityCount());
        self::assertSame(1, $first->optionalCapabilityCount());
        self::assertSame(2, $first->permittedCapabilityCount());
        self::assertFalse($first->runtimeEvidenceSupplied());
        self::assertSame([], $first->toArray()['runtime_summary']);
        self::assertSame('not-established', $first->externalAuthorityCurrentness());
        self::assertSame('not-sandboxed', $first->trustedCodeIsolation());

        $serialized = $first->toArray();
        $identity = $serialized['inspection_identity'];
        unset($serialized['inspection_identity']);
        self::assertSame(
            hash('sha256', json_encode($serialized, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)),
            $identity,
        );
        $serialized['profile_identity'] = str_repeat('f', 64);
        self::assertNotSame($serialized, $first->toArray());
    }

    public function testCompletedRuntimeSummaryCopiesEveryBoundedField(): void
    {
        $inspection = AgentInspectionFactory::inspection(true);
        $summary = $inspection->toArray()['runtime_summary'];
        self::assertIsArray($summary);
        self::assertIsArray($summary['usage']);

        self::assertTrue($inspection->runtimeEvidenceSupplied());
        self::assertSame('performed', $summary['outcome']);
        self::assertSame('retired', $summary['terminal_state']);
        self::assertSame([2, 10, 5, 1, 0, 0, 7, 0, 1], array_values($summary['usage']));
        self::assertSame(1, $summary['receipt_count']);
        self::assertTrue($summary['capability_result_present']);
        self::assertTrue($summary['validated_proposal_present']);
        self::assertTrue($summary['cleanup_completed_without_failure']);
    }

    public function testSuppliedWiringObjectCannotContradictValidatedCanonicalBytes(): void
    {
        $profile = AgentInspectionFactory::profile();
        $valid = AgentInspectionFactory::wiring($profile);
        $contradictory = new GeneratedAgentWiring(
            new AgentApplicationProfileIdentity(str_repeat('0', 64)),
            $valid->declarationArtifactSha256(),
            $valid->declarationIdentity(),
            $valid->admissionIdentity(),
            $valid->permittedCapabilityIdentities(),
            $valid->implementationSha256(),
            $valid->generatedNamespace(),
            $valid->generatedClass(),
            $valid->canonicalBytes(),
            $valid->generatedWiringSha256(),
        );

        $this->expectException(AgentInspectionBindingException::class);
        (new AgentPrincipalInspector())->inspect(new AgentPrincipalInspectionRequest($profile, $contradictory));
    }

    public function testEverySuppliedWiringBindingFieldMustMatchValidatedBytes(): void
    {
        $profile = AgentInspectionFactory::profile();
        $valid = AgentInspectionFactory::wiring($profile);
        foreach (['artifact', 'declaration', 'admission', 'capability_order', 'implementation', 'namespace', 'class', 'wiring_digest'] as $field) {
            try {
                (new AgentPrincipalInspector())->inspect(new AgentPrincipalInspectionRequest($profile, $this->contradictoryWiring($valid, $field)));
                self::fail('Expected the contradictory generated wiring field to fail.');
            } catch (AgentInspectionBindingException $failure) {
                self::assertSame('KGN-AGENT-INSPECTION-1347', $failure->diagnosticId());
            }
        }
    }

    public function testConstraintAndEvidenceReferencePlanMismatchesFailBeforeProjection(): void
    {
        $profile = AgentInspectionFactory::profile();
        $plan = $profile->authorityPlan();
        $constraints = $plan->constraints();
        $constraints[0] = new AgentConstraint($constraints[0]->kind(), $constraints[0]->value() + 1);
        $references = $plan->evidenceReferences();
        $references[0] = new EvidenceReference($references[0]->kind(), $references[0]->identity(), str_repeat('f', 64));

        foreach ([$this->profileWithPlan($profile, $this->planWith($plan, $constraints, $plan->evidenceReferences())), $this->profileWithPlan($profile, $this->planWith($plan, $plan->constraints(), $references))] as $mismatch) {
            try {
                (new AgentPrincipalInspector())->inspect(new AgentPrincipalInspectionRequest($mismatch, AgentInspectionFactory::wiring($profile)));
                self::fail('Expected the declaration/plan aggregate mismatch to fail.');
            } catch (AgentInspectionBindingException $failure) {
                self::assertSame('KGN-AGENT-INSPECTION-1347', $failure->diagnosticId());
            }
        }
    }

    public function testReceiptForAnotherPrincipalIsRejectedWithoutDisclosure(): void
    {
        $profile = AgentInspectionFactory::profile();
        $result = AgentInspectionFactory::runtimeResult($profile->declaration()->principal());
        $receipt = $result->receipts()[0];
        $private = 'private-principal-literal';
        $other = new AgentPrincipalReference(new PrincipalReference(PrincipalKind::Agent, new PrincipalId($private)));
        $badReceipt = new AgentReceipt(new ReceiptIdentity('receipt.other'), $other, $receipt->operation(), $receipt->proposalIdentity(), ReceiptOutcome::Performed, str_repeat('f', 64), $receipt->provenance(), $receipt->visibility());
        $bad = new AgentRuntimeResult($result->outcome(), $result->terminalState(), $result->usage(), $result->validatedProposal(), $result->capabilityResult(), [$badReceipt], true);

        try {
            (new AgentPrincipalInspector())->inspect(new AgentPrincipalInspectionRequest($profile, AgentInspectionFactory::wiring($profile), $bad));
            self::fail('Expected invalid completed runtime evidence.');
        } catch (InvalidCompletedRuntimeEvidenceException $failure) {
            self::assertStringNotContainsString($private, $failure->getMessage());
            self::assertStringNotContainsString($private, (string) $failure);
        }
    }

    private function contradictoryWiring(GeneratedAgentWiring $valid, string $field): GeneratedAgentWiring
    {
        $capabilities = $valid->permittedCapabilityIdentities();
        if ('capability_order' === $field) {
            $capabilities = array_reverse($capabilities);
        }

        return new GeneratedAgentWiring(
            $valid->profileIdentity(),
            'artifact' === $field ? str_repeat('0', 64) : $valid->declarationArtifactSha256(),
            'declaration' === $field ? new AgentDeclarationIdentity('declaration.other') : $valid->declarationIdentity(),
            'admission' === $field ? new AgentAdmissionIdentity(str_repeat('0', 64)) : $valid->admissionIdentity(),
            $capabilities,
            'implementation' === $field ? str_repeat('0', 64) : $valid->implementationSha256(),
            'namespace' === $field ? 'PrivateGeneratedNamespace' : $valid->generatedNamespace(),
            'class' === $field ? 'PrivateGeneratedClass' : $valid->generatedClass(),
            $valid->canonicalBytes(),
            'wiring_digest' === $field ? str_repeat('0', 64) : $valid->generatedWiringSha256(),
        );
    }

    /**
     * @param list<AgentConstraint> $constraints
     * @param list<EvidenceReference> $references
     */
    private function planWith(AgentAuthorityPlan $plan, array $constraints, array $references): AgentAuthorityPlan
    {
        return new AgentAuthorityPlan(
            $plan->identity(),
            $plan->declarationIdentity(),
            $plan->declarationSha256(),
            $plan->principal(),
            $plan->authenticationOutcome(),
            $plan->admissionDecision(),
            $plan->capabilityDecisions(),
            $constraints,
            $references,
        );
    }

    private function profileWithPlan(AgentApplicationProfile $profile, AgentAuthorityPlan $plan): AgentApplicationProfile
    {
        return new AgentApplicationProfile(
            $profile->declaration(),
            $profile->declarationArtifact(),
            $plan,
            $profile->identity(),
            $profile->permittedCapabilityIdentities(),
        );
    }
}
