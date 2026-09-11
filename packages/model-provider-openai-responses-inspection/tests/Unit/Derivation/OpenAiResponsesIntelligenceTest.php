<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponsesInspection\Tests\Unit\Derivation;

use Kongono\ApplicationGraphIntelligence\Role\DeveloperIntelligenceRole as GraphRole;
use Kongono\ModelProviderOpenAiResponsesInspection\Derivation\OpenAiResponsesIntelligenceDeriver;
use Kongono\ModelProviderOpenAiResponsesInspection\Derivation\OpenAiResponsesIntelligenceView;
use Kongono\ModelProviderOpenAiResponsesInspection\Exception\InvalidOpenAiResponsesIntelligenceEvidenceException;
use Kongono\ModelProviderOpenAiResponsesInspection\Graph\OpenAiResponsesGraphEvidence;
use Kongono\ModelProviderOpenAiResponsesInspection\Graph\OpenAiResponsesGraphEvidenceProjector;
use Kongono\ModelProviderOpenAiResponsesInspection\Inspection\OpenAiResponsesInspectionRequest;
use Kongono\ModelProviderOpenAiResponsesInspection\Inspection\OpenAiResponsesInspector;
use Kongono\ModelProviderOpenAiResponsesInspection\Role\OpenAiResponsesInspectionDeveloperRole;
use Kongono\ModelProviderOpenAiResponsesInspection\Tests\Support\OpenAiResponsesInspectionFactory;
use Kongono\ModelProviderOpenAiResponsesInspection\Verification\OpenAiResponsesIntelligenceVerdict;
use Kongono\ModelProviderOpenAiResponsesInspection\Verification\OpenAiResponsesIntelligenceVerifier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OpenAiResponsesIntelligenceTest extends TestCase
{
    /** @return iterable<string, array{OpenAiResponsesInspectionDeveloperRole,string}> */
    public static function roles(): iterable
    {
        yield 'chie' => [OpenAiResponsesInspectionDeveloperRole::Chie, 'explain'];
        yield 'hantei' => [OpenAiResponsesInspectionDeveloperRole::Hantei, 'verify'];
        yield 'oshie' => [OpenAiResponsesInspectionDeveloperRole::Oshie, 'teach'];
    }

    #[DataProvider('roles')]
    public function testRoleMappingIsExactAndExecutesNothing(OpenAiResponsesInspectionDeveloperRole $role, string $purpose): void
    {
        $inspection = $this->inspection();
        $view = (new OpenAiResponsesIntelligenceDeriver())->derive($inspection, $role);

        self::assertSame($purpose, $view->purpose());
        self::assertSame('not-performed', $view->roleExecution());
        self::assertSame('not-performed', $view->repair());
        self::assertSame('none', $view->hiddenDataAccess());
        self::assertSame('not-performed', $view->networking());
        self::assertSame('not-performed', $view->providerObservation());
        self::assertSame('not-performed', $view->replay());
        self::assertSame('not-performed', $view->recording());
        self::assertSame('not-performed', $view->exporting());
        self::assertSame('not-performed', $view->certification());
        self::assertSame('not-evaluated', $view->authority());
    }

    public function testGraphAbsenceAndMatchingRoleAggregateEvidenceRemainDistinct(): void
    {
        $inspection = $this->inspection();
        $projector = new OpenAiResponsesGraphEvidenceProjector();
        $absent = $projector->project($inspection, OpenAiResponsesInspectionDeveloperRole::Chie);
        $supplied = $projector->project($inspection, OpenAiResponsesInspectionDeveloperRole::Chie, OpenAiResponsesInspectionFactory::graphView());

        self::assertFalse($absent->supplied());
        self::assertTrue($supplied->supplied());
        self::assertSame(OpenAiResponsesGraphEvidence::RELATIONSHIP, $supplied->relationship());
        $graph = $supplied->toArray()['graph'];
        self::assertIsArray($graph);
        self::assertSame('not-established', $graph['currentness']);
        self::assertSame('evidence-only', $graph['authority']);
    }

    public function testGraphRoleMismatchFailsClosed(): void
    {
        $this->expectException(InvalidOpenAiResponsesIntelligenceEvidenceException::class);
        (new OpenAiResponsesGraphEvidenceProjector())->project(
            $this->inspection(),
            OpenAiResponsesInspectionDeveloperRole::Chie,
            OpenAiResponsesInspectionFactory::graphView(GraphRole::Hantei),
        );
    }

    public function testIndependentVerifierPassesEveryOrderedGroupWithoutCallingDeriver(): void
    {
        $inspection = $this->inspection();
        $graph = OpenAiResponsesInspectionFactory::graphView(GraphRole::Hantei);
        $view = (new OpenAiResponsesIntelligenceDeriver())->derive($inspection, OpenAiResponsesInspectionDeveloperRole::Hantei, $graph);
        $first = (new OpenAiResponsesIntelligenceVerifier())->verify($inspection, $view, $graph);
        $second = (new OpenAiResponsesIntelligenceVerifier())->verify($inspection, $view, $graph);

        self::assertSame(OpenAiResponsesIntelligenceVerdict::Pass, $first->verdict());
        self::assertSame([], $first->failureTokens());
        self::assertSame($first->toArray(), $second->toArray());
        self::assertNotEmpty($first->checks());
        self::assertNotContains(false, $first->checks());
    }

    public function testIndependentVerifierDetectsRoleAndSerializationMismatchWithoutRepair(): void
    {
        $inspection = $this->inspection();
        $graph = new OpenAiResponsesGraphEvidence($inspection->identity(), OpenAiResponsesInspectionDeveloperRole::Chie, false);
        $candidate = new OpenAiResponsesIntelligenceView(
            OpenAiResponsesInspectionDeveloperRole::Chie,
            'teach',
            $inspection,
            $graph,
            roleExecution: 'not-observed',
            serializationSha256: str_repeat('0', 64),
        );
        $verification = (new OpenAiResponsesIntelligenceVerifier())->verify($inspection, $candidate);

        self::assertSame(OpenAiResponsesIntelligenceVerdict::Fail, $verification->verdict());
        self::assertContains('role-purpose-failed', $verification->failureTokens());
        self::assertContains('role-execution-repair-hidden-access-provider-semantics-failed', $verification->failureTokens());
        self::assertContains('deterministic-serialization-failed', $verification->failureTokens());
        self::assertSame('not-observed', $candidate->roleExecution());
    }

    private function inspection(): \Kongono\ModelProviderOpenAiResponsesInspection\Inspection\OpenAiResponsesInspection
    {
        $profile = OpenAiResponsesInspectionFactory::profile();

        return (new OpenAiResponsesInspector())->inspect(new OpenAiResponsesInspectionRequest($profile));
    }
}
