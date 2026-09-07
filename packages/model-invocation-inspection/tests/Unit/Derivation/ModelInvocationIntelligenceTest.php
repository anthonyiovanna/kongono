<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationInspection\Tests\Unit\Derivation;

use Kongono\ApplicationGraphIntelligence\Role\DeveloperIntelligenceRole as GraphRole;
use Kongono\ModelInvocationInspection\Derivation\ModelInvocationIntelligenceDeriver;
use Kongono\ModelInvocationInspection\Derivation\ModelInvocationIntelligenceView;
use Kongono\ModelInvocationInspection\Exception\InvalidModelInvocationIntelligenceEvidenceException;
use Kongono\ModelInvocationInspection\Graph\ModelInvocationGraphEvidence;
use Kongono\ModelInvocationInspection\Graph\ModelInvocationGraphEvidenceProjector;
use Kongono\ModelInvocationInspection\Inspection\ModelInvocationInspectionRequest;
use Kongono\ModelInvocationInspection\Inspection\ModelInvocationInspector;
use Kongono\ModelInvocationInspection\Role\ModelInspectionDeveloperRole;
use Kongono\ModelInvocationInspection\Tests\Support\ModelInvocationInspectionFactory;
use Kongono\ModelInvocationInspection\Verification\ModelInvocationIntelligenceVerdict;
use Kongono\ModelInvocationInspection\Verification\ModelInvocationIntelligenceVerifier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ModelInvocationIntelligenceTest extends TestCase
{
    /** @return iterable<string, array{ModelInspectionDeveloperRole,string}> */
    public static function roles(): iterable
    {
        yield 'chie' => [ModelInspectionDeveloperRole::Chie, 'explain'];
        yield 'hantei' => [ModelInspectionDeveloperRole::Hantei, 'verify'];
        yield 'oshie' => [ModelInspectionDeveloperRole::Oshie, 'teach'];
    }

    #[DataProvider('roles')]
    public function testRoleMappingIsExactAndExecutesNothing(ModelInspectionDeveloperRole $role, string $purpose): void
    {
        $inspection = $this->inspection();
        $view = (new ModelInvocationIntelligenceDeriver())->derive($inspection, $role);

        self::assertSame($purpose, $view->purpose());
        self::assertSame('not-performed', $view->roleExecution());
        self::assertSame('not-performed', $view->repair());
        self::assertSame('none', $view->hiddenDataAccess());
        self::assertSame('not-performed', $view->providerInvocation());
        self::assertSame('evidence-only', $view->authority());
    }

    public function testGraphAbsenceAndMatchingRoleAggregateEvidenceRemainDistinct(): void
    {
        $inspection = $this->inspection();
        $projector = new ModelInvocationGraphEvidenceProjector();
        $absent = $projector->project($inspection, ModelInspectionDeveloperRole::Chie);
        $supplied = $projector->project($inspection, ModelInspectionDeveloperRole::Chie, ModelInvocationInspectionFactory::graphView());

        self::assertFalse($absent->supplied());
        self::assertTrue($supplied->supplied());
        self::assertSame(ModelInvocationGraphEvidence::RELATIONSHIP, $supplied->relationship());
        $graph = $supplied->toArray()['graph'];
        self::assertIsArray($graph);
        self::assertSame('not-established', $graph['currentness']);
        self::assertSame('evidence-only', $graph['authority']);
    }

    public function testGraphRoleMismatchFailsClosed(): void
    {
        $this->expectException(InvalidModelInvocationIntelligenceEvidenceException::class);
        (new ModelInvocationGraphEvidenceProjector())->project(
            $this->inspection(),
            ModelInspectionDeveloperRole::Chie,
            ModelInvocationInspectionFactory::graphView(GraphRole::Hantei),
        );
    }

    public function testIndependentVerifierPassesEveryOrderedGroupWithoutCallingDeriver(): void
    {
        $inspection = $this->inspection();
        $graph = ModelInvocationInspectionFactory::graphView(GraphRole::Hantei);
        $view = (new ModelInvocationIntelligenceDeriver())->derive($inspection, ModelInspectionDeveloperRole::Hantei, $graph);
        $first = (new ModelInvocationIntelligenceVerifier())->verify($inspection, $view, $graph);
        $second = (new ModelInvocationIntelligenceVerifier())->verify($inspection, $view, $graph);

        self::assertSame(ModelInvocationIntelligenceVerdict::Pass, $first->verdict());
        self::assertSame([], $first->failureTokens());
        self::assertSame($first->toArray(), $second->toArray());
        self::assertNotEmpty($first->checks());
        self::assertNotContains(false, $first->checks());
    }

    public function testIndependentVerifierDetectsRoleAndSerializationMismatchWithoutRepair(): void
    {
        $inspection = $this->inspection();
        $graph = new ModelInvocationGraphEvidence($inspection->identity(), ModelInspectionDeveloperRole::Chie, false);
        $candidate = new ModelInvocationIntelligenceView(
            ModelInspectionDeveloperRole::Chie,
            'teach',
            $inspection,
            $graph,
            roleExecution: 'not-observed',
            serializationSha256: str_repeat('0', 64),
        );
        $verification = (new ModelInvocationIntelligenceVerifier())->verify($inspection, $candidate);

        self::assertSame(ModelInvocationIntelligenceVerdict::Fail, $verification->verdict());
        self::assertContains('role-purpose-failed', $verification->failureTokens());
        self::assertContains('role-execution-repair-hidden-access-provider-semantics-failed', $verification->failureTokens());
        self::assertContains('deterministic-serialization-failed', $verification->failureTokens());
        self::assertSame('not-observed', $candidate->roleExecution());
    }

    private function inspection(): \Kongono\ModelInvocationInspection\Inspection\ModelInvocationInspection
    {
        $profile = ModelInvocationInspectionFactory::profile();

        return (new ModelInvocationInspector())->inspect(new ModelInvocationInspectionRequest($profile, ModelInvocationInspectionFactory::wiring($profile)));
    }
}
