<?php

declare(strict_types=1);

namespace Kongono\AgentInspection\Tests\Unit\Derivation;

use Kongono\AgentInspection\Derivation\AgentIntelligenceDeriver;
use Kongono\AgentInspection\Derivation\AgentIntelligenceView;
use Kongono\AgentInspection\Graph\AgentGraphEvidence;
use Kongono\AgentInspection\Inspection\AgentPrincipalInspection;
use Kongono\AgentInspection\Role\DeveloperIntelligenceRole;
use Kongono\AgentInspection\Tests\Support\AgentInspectionFactory;
use Kongono\AgentInspection\Verification\AgentIntelligenceVerdict;
use Kongono\AgentInspection\Verification\AgentIntelligenceVerifier;
use PHPUnit\Framework\TestCase;

final class AgentIntelligenceTest extends TestCase
{
    public function testRolesDifferOnlyByRoleAndPurpose(): void
    {
        $inspection = AgentInspectionFactory::inspection();
        $deriver = new AgentIntelligenceDeriver();
        $views = [];
        foreach (DeveloperIntelligenceRole::cases() as $role) {
            $views[] = $deriver->derive($inspection, $role);
        }
        $arrays = array_map(static fn(AgentIntelligenceView $view): array => $view->toArray(), $views);
        foreach ($arrays as &$array) {
            unset($array['role'], $array['purpose'], $array['serialization_sha256']);
            self::assertIsArray($array['graph_evidence']);
            $array['graph_evidence']['role'] = 'normalized';
        }
        unset($array);
        self::assertSame($arrays[0], $arrays[1]);
        self::assertSame($arrays[1], $arrays[2]);
    }

    public function testIndependentVerificationPassesExactEvidenceAndFailsFixedGroups(): void
    {
        $inspection = AgentInspectionFactory::inspection();
        $graph = AgentInspectionFactory::graphView();
        $exact = (new AgentIntelligenceDeriver())->derive($inspection, DeveloperIntelligenceRole::Hantei, $graph);
        $verifier = new AgentIntelligenceVerifier();

        self::assertSame(AgentIntelligenceVerdict::Pass, $verifier->verify($inspection, $exact, $graph)->verdict());

        $wrongPurpose = new AgentIntelligenceView(DeveloperIntelligenceRole::Hantei, 'explain', $inspection, $exact->graphEvidence());
        self::assertSame(['role-purpose-failed'], $verifier->verify($inspection, $wrongPurpose, $graph)->failureTokens());

        $wrongIdentityInspection = $this->tamperInspection($inspection, $inspection->identity(), str_repeat('0', 64));
        $wrongIdentity = new AgentIntelligenceView(DeveloperIntelligenceRole::Hantei, 'verify', $wrongIdentityInspection, $exact->graphEvidence());
        self::assertSame(['inspection-identity-schema-failed'], $verifier->verify($inspection, $wrongIdentity, $graph)->failureTokens());

        $wrongAggregateInspection = $this->tamperInspection($inspection, 'assistant.primary', 'assistant.changed');
        $wrongAggregate = new AgentIntelligenceView(DeveloperIntelligenceRole::Hantei, 'verify', $wrongAggregateInspection, $exact->graphEvidence());
        self::assertSame(['inspection-aggregate-evidence-failed'], $verifier->verify($inspection, $wrongAggregate, $graph)->failureTokens());

        $runtimeInspection = AgentInspectionFactory::inspection(true);
        $runtimeExact = (new AgentIntelligenceDeriver())->derive($runtimeInspection, DeveloperIntelligenceRole::Hantei);
        $wrongRuntimeInspection = $this->tamperInspection($runtimeInspection, 'receiptCount";i:1;', 'receiptCount";i:0;');
        $wrongRuntime = new AgentIntelligenceView(DeveloperIntelligenceRole::Hantei, 'verify', $wrongRuntimeInspection, $runtimeExact->graphEvidence());
        self::assertSame(['runtime-summary-evidence-failed'], $verifier->verify($runtimeInspection, $wrongRuntime)->failureTokens());

        $wrongGraph = new AgentIntelligenceView(
            DeveloperIntelligenceRole::Hantei,
            'verify',
            $inspection,
            new AgentGraphEvidence($inspection->identity(), DeveloperIntelligenceRole::Hantei, false),
        );
        self::assertSame(['graph-evidence-failed'], $verifier->verify($inspection, $wrongGraph, $graph)->failureTokens());

        $wrongSemanticsInspection = $this->tamperInspection($inspection, 'safe-aggregate-only', 'not-established----');
        $wrongSemantics = new AgentIntelligenceView(DeveloperIntelligenceRole::Hantei, 'verify', $wrongSemanticsInspection, $exact->graphEvidence());
        self::assertSame(['disclosure-currentness-authority-semantics-failed'], $verifier->verify($inspection, $wrongSemantics, $graph)->failureTokens());

        $wrongRoleSemantics = new AgentIntelligenceView(DeveloperIntelligenceRole::Hantei, 'verify', $inspection, $exact->graphEvidence(), roleExecution: 'not-observed');
        self::assertSame(['role-execution-repair-hidden-access-semantics-failed'], $verifier->verify($inspection, $wrongRoleSemantics, $graph)->failureTokens());

        $wrongSerialization = new AgentIntelligenceView(DeveloperIntelligenceRole::Hantei, 'verify', $inspection, $exact->graphEvidence(), serializationSha256: str_repeat('0', 64));
        self::assertSame(['deterministic-serialization-failed'], $verifier->verify($inspection, $wrongSerialization, $graph)->failureTokens());
    }

    public function testRepeatedNestedAndInterleavedOperationsAreStable(): void
    {
        $inspection = AgentInspectionFactory::inspection(true);
        $deriver = new AgentIntelligenceDeriver();
        $first = $deriver->derive($inspection, DeveloperIntelligenceRole::Chie);
        $middle = $deriver->derive($inspection, DeveloperIntelligenceRole::Oshie);
        $second = $deriver->derive($inspection, DeveloperIntelligenceRole::Chie);

        self::assertSame($first->toArray(), $second->toArray());
        self::assertNotSame($first->toArray()['role'], $middle->toArray()['role']);
    }

    private function tamperInspection(AgentPrincipalInspection $inspection, string $from, string $to): AgentPrincipalInspection
    {
        self::assertSame(strlen($from), strlen($to));
        $serialized = str_replace($from, $to, serialize($inspection), $replacements);
        self::assertSame(1, $replacements);
        $candidate = unserialize($serialized, ['allowed_classes' => true]);
        self::assertInstanceOf(AgentPrincipalInspection::class, $candidate);

        return $candidate;
    }
}
