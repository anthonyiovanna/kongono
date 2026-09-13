<?php

declare(strict_types=1);

namespace Kongono\ViewInspection\Tests\Unit\Derivation;

use Kongono\ApplicationGraphIntelligence\Role\DeveloperIntelligenceRole as GraphRole;
use Kongono\ViewInspection\Derivation\ViewIntelligenceDeriver;
use Kongono\ViewInspection\Derivation\ViewIntelligenceView;
use Kongono\ViewInspection\Exception\InvalidViewIntelligenceEvidenceException;
use Kongono\ViewInspection\Graph\ViewGraphEvidence;
use Kongono\ViewInspection\Graph\ViewGraphEvidenceProjector;
use Kongono\ViewInspection\Inspection\ViewInspectionRequest;
use Kongono\ViewInspection\Inspection\ViewInspector;
use Kongono\ViewInspection\Role\ViewInspectionDeveloperRole;
use Kongono\ViewInspection\Tests\Support\ViewInspectionFactory;
use Kongono\ViewInspection\Verification\ViewIntelligenceVerdict;
use Kongono\ViewInspection\Verification\ViewIntelligenceVerifier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ViewIntelligenceTest extends TestCase
{
    /** @return iterable<string, array{ViewInspectionDeveloperRole,string}> */
    public static function roles(): iterable
    {
        yield 'chie' => [ViewInspectionDeveloperRole::Chie, 'explain'];
        yield 'hantei' => [ViewInspectionDeveloperRole::Hantei, 'verify'];
        yield 'oshie' => [ViewInspectionDeveloperRole::Oshie, 'teach'];
    }

    #[DataProvider('roles')]
    public function testRoleMappingAndFixedStatementsAreExactWithoutExecution(ViewInspectionDeveloperRole $role, string $purpose): void
    {
        $view = (new ViewIntelligenceDeriver())->derive($this->inspection(), $role);

        self::assertSame($purpose, $view->purpose());
        self::assertSame(ViewIntelligenceView::STATEMENTS, $view->statements());
        self::assertSame(ViewIntelligenceView::LIMITATIONS, $view->limitations());
        self::assertSame('not-performed', $view->roleExecution());
        self::assertSame('not-performed', $view->repair());
        self::assertSame('none', $view->hiddenDataAccess());
        self::assertSame('not-performed', $view->filesystemAccess());
        self::assertSame('not-performed', $view->generatedPhpExecution());
        self::assertSame('not-performed', $view->rendering());
        self::assertSame('not-performed', $view->productionObservation());
        self::assertSame('not-performed', $view->recording());
        self::assertSame('not-performed', $view->exporting());
        self::assertSame('not-performed', $view->certification());
        self::assertSame('evidence-only', $view->authority());
    }

    public function testGraphAbsenceAndMatchingRoleCopyOnlySafeAggregates(): void
    {
        $inspection = $this->inspection();
        $projector = new ViewGraphEvidenceProjector();
        $absent = $projector->project($inspection, ViewInspectionDeveloperRole::Chie);
        $supplied = $projector->project($inspection, ViewInspectionDeveloperRole::Chie, ViewInspectionFactory::graphView());

        self::assertFalse($absent->supplied());
        self::assertTrue($supplied->supplied());
        self::assertSame('not-established', $absent->relationship());
        self::assertSame('not-established', $supplied->relationship());
        $serialized = json_encode($supplied->toArray(), JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString('artifact_sha256', $serialized);
        self::assertStringNotContainsString('graph_identity', $serialized);
        self::assertStringContainsString('safe-aggregate-only', $serialized);
    }

    public function testGraphRoleMismatchFailsClosed(): void
    {
        $this->expectException(InvalidViewIntelligenceEvidenceException::class);
        (new ViewGraphEvidenceProjector())->project(
            $this->inspection(),
            ViewInspectionDeveloperRole::Chie,
            ViewInspectionFactory::graphView(GraphRole::Hantei),
        );
    }

    public function testIndependentVerifierPassesExactEvidenceDeterministically(): void
    {
        $inspection = $this->inspection();
        $graph = ViewInspectionFactory::graphView(GraphRole::Hantei);
        $view = (new ViewIntelligenceDeriver())->derive($inspection, ViewInspectionDeveloperRole::Hantei, $graph);
        $first = (new ViewIntelligenceVerifier())->verify($inspection, $view, $graph);
        $second = (new ViewIntelligenceVerifier())->verify($inspection, $view, $graph);

        self::assertSame(ViewIntelligenceVerdict::Pass, $first->verdict());
        self::assertSame([], $first->failureTokens());
        self::assertNotContains(false, $first->checks());
        self::assertSame($first->toArray(), $second->toArray());
    }

    public function testIndependentVerifierFailsEveryTamperWithoutRepair(): void
    {
        $inspection = $this->inspection();
        $absent = new ViewGraphEvidence($inspection->identity(), ViewInspectionDeveloperRole::Chie, false);
        $baseline = new ViewIntelligenceView(ViewInspectionDeveloperRole::Chie, 'explain', $inspection, $absent);
        $cases = [
            'role-purpose-failed' => new ViewIntelligenceView(ViewInspectionDeveloperRole::Chie, 'teach', $inspection, $absent),
            'ordered-statement-evidence-failed' => new ViewIntelligenceView(ViewInspectionDeveloperRole::Chie, 'explain', $inspection, $absent, statements: array_reverse(ViewIntelligenceView::STATEMENTS)),
            'ordered-limitation-evidence-failed' => new ViewIntelligenceView(ViewInspectionDeveloperRole::Chie, 'explain', $inspection, $absent, limitations: array_reverse(ViewIntelligenceView::LIMITATIONS)),
            'no-effect-markers-failed' => new ViewIntelligenceView(ViewInspectionDeveloperRole::Chie, 'explain', $inspection, $absent, roleExecution: 'not-observed'),
            'deterministic-serialization-failed' => new ViewIntelligenceView(ViewInspectionDeveloperRole::Chie, 'explain', $inspection, $absent, serializationSha256: str_repeat('0', 64)),
        ];
        foreach ($cases as $expected => $candidate) {
            $verification = (new ViewIntelligenceVerifier())->verify($inspection, $candidate);
            self::assertSame(ViewIntelligenceVerdict::Fail, $verification->verdict(), $expected);
            self::assertContains($expected, $verification->failureTokens());
            self::assertSame($candidate->toArray(), $candidate->toArray());
        }

        $different = (new ViewInspector())->inspect(new ViewInspectionRequest(ViewInspectionFactory::profile('different', true)));
        $differentView = (new ViewIntelligenceDeriver())->derive($different, ViewInspectionDeveloperRole::Chie);
        $identity = (new ViewIntelligenceVerifier())->verify($inspection, $differentView);
        self::assertContains('inspection-identity-schema-failed', $identity->failureTokens());
        self::assertContains('format-aggregate-and-flag-evidence-failed', $identity->failureTokens());

        $graphCandidate = (new ViewIntelligenceDeriver())->derive($inspection, ViewInspectionDeveloperRole::Chie, ViewInspectionFactory::graphView());
        $graphTamper = (new ViewIntelligenceVerifier())->verify($inspection, $graphCandidate, ViewInspectionFactory::graphView(serviceCount: 5));
        self::assertContains('graph-evidence-failed', $graphTamper->failureTokens());

        $roleTamper = new ViewIntelligenceView(
            ViewInspectionDeveloperRole::Oshie,
            'teach',
            $inspection,
            new ViewGraphEvidence($inspection->identity(), ViewInspectionDeveloperRole::Oshie, false),
            serializationSha256: $baseline->serializationSha256(),
        );
        self::assertContains('deterministic-serialization-failed', (new ViewIntelligenceVerifier())->verify($inspection, $roleTamper)->failureTokens());
    }

    private function inspection(): \Kongono\ViewInspection\Inspection\ViewInspection
    {
        return (new ViewInspector())->inspect(new ViewInspectionRequest(ViewInspectionFactory::profile()));
    }
}
