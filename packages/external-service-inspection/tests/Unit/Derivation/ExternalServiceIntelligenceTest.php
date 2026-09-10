<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceInspection\Tests\Unit\Derivation;

use Kongono\ApplicationGraphIntelligence\Role\DeveloperIntelligenceRole as GraphRole;
use Kongono\ExternalServiceInspection\Derivation\ExternalServiceIntelligenceDeriver;
use Kongono\ExternalServiceInspection\Derivation\ExternalServiceIntelligenceView;
use Kongono\ExternalServiceInspection\Exception\InvalidExternalServiceIntelligenceEvidenceException;
use Kongono\ExternalServiceInspection\Graph\ExternalServiceGraphEvidence;
use Kongono\ExternalServiceInspection\Inspection\ExternalServiceInspectionRequest;
use Kongono\ExternalServiceInspection\Inspection\ExternalServiceInspector;
use Kongono\ExternalServiceInspection\Role\ExternalServiceInspectionDeveloperRole;
use Kongono\ExternalServiceInspection\Tests\Support\ExternalServiceInspectionFactory as Fixture;
use Kongono\ExternalServiceInspection\Verification\ExternalServiceIntelligenceVerdict;
use Kongono\ExternalServiceInspection\Verification\ExternalServiceIntelligenceVerifier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ExternalServiceIntelligenceTest extends TestCase
{
    #[DataProvider('roles')]
    public function testAllRolesDeriveDeterministicallyAndVerifyIndependently(ExternalServiceInspectionDeveloperRole $role): void
    {
        $profile = Fixture::profile();
        $inspection = (new ExternalServiceInspector())->inspect(new ExternalServiceInspectionRequest($profile));
        $deriver = new ExternalServiceIntelligenceDeriver();
        $first = $deriver->derive($inspection, $role);
        $second = $deriver->derive($inspection, $role);

        self::assertSame($role->purpose(), $first->purpose());
        self::assertSame($first->canonicalBytes(), $second->canonicalBytes());
        self::assertSame(ExternalServiceIntelligenceView::statementsFor($role), $first->statements());
        self::assertSame('not-performed', $first->roleExecution());
        self::assertSame('not-performed', $first->credentialAccess());
        self::assertSame('not-performed', $first->networkAccess());
        self::assertSame('not-performed', $first->authorityEvaluation());
        self::assertSame('not-performed', $first->repair());
        self::assertSame('not-performed', $first->replay());
        self::assertSame('not-performed', $first->certification());
        self::assertSame('none', $first->hiddenDataAccess());
        self::assertSame('evidence-only', $first->authority());
        self::assertSame(ExternalServiceIntelligenceVerdict::Pass, (new ExternalServiceIntelligenceVerifier())->verify($inspection, $first)->verdict());
    }

    public function testMatchingGraphCopiesOnlySafeAggregatesAndMismatchedRoleFailsClosed(): void
    {
        $profile = Fixture::profile();
        $inspection = (new ExternalServiceInspector())->inspect(new ExternalServiceInspectionRequest($profile));
        $view = (new ExternalServiceIntelligenceDeriver())->derive(
            $inspection,
            ExternalServiceInspectionDeveloperRole::Chie,
            Fixture::graphView(GraphRole::Chie),
        );

        self::assertTrue($view->graphEvidence()->supplied());
        self::assertSame(ExternalServiceGraphEvidence::RELATIONSHIP, $view->graphEvidence()->relationship());
        $graph = $view->graphEvidence()->toArray()['graph'];
        self::assertIsArray($graph);
        self::assertSame(10, $graph['node_count']);
        self::assertSame(ExternalServiceIntelligenceVerdict::Pass, (new ExternalServiceIntelligenceVerifier())->verify($inspection, $view, Fixture::graphView(GraphRole::Chie))->verdict());

        $this->expectException(InvalidExternalServiceIntelligenceEvidenceException::class);
        (new ExternalServiceIntelligenceDeriver())->derive(
            $inspection,
            ExternalServiceInspectionDeveloperRole::Chie,
            Fixture::graphView(GraphRole::Hantei),
        );
    }

    public function testVerifierRejectsAlteredDigestWithoutRepairOrDerivation(): void
    {
        $profile = Fixture::profile();
        $inspection = (new ExternalServiceInspector())->inspect(new ExternalServiceInspectionRequest($profile));
        $role = ExternalServiceInspectionDeveloperRole::Hantei;
        $candidate = new ExternalServiceIntelligenceView(
            $role,
            $role->purpose(),
            $inspection,
            new ExternalServiceGraphEvidence($inspection->identity(), $role, false),
            ['inspection-bindings', 'optional-completed-result-evidence', 'graph-aggregates', 'limitations'],
            ExternalServiceIntelligenceView::statementsFor($role),
            serializationSha256: str_repeat('f', 64),
        );

        $verification = (new ExternalServiceIntelligenceVerifier())->verify($inspection, $candidate);
        self::assertSame(ExternalServiceIntelligenceVerdict::Fail, $verification->verdict());
        self::assertSame(['deterministic-serialization-failed'], $verification->failureTokens());
        self::assertSame('not-performed', $candidate->repair());
    }

    public function testCandidateCannotExpandOrderedEvidenceGroups(): void
    {
        $profile = Fixture::profile();
        $inspection = (new ExternalServiceInspector())->inspect(new ExternalServiceInspectionRequest($profile));
        $role = ExternalServiceInspectionDeveloperRole::Oshie;

        $this->expectException(InvalidExternalServiceIntelligenceEvidenceException::class);
        new ExternalServiceIntelligenceView(
            $role,
            $role->purpose(),
            $inspection,
            new ExternalServiceGraphEvidence($inspection->identity(), $role, false),
            ['inspection-bindings', 'hidden-data', 'limitations'],
            ExternalServiceIntelligenceView::statementsFor($role),
        );
    }

    /** @return list<array{ExternalServiceInspectionDeveloperRole}> */
    public static function roles(): array
    {
        return array_map(static fn(ExternalServiceInspectionDeveloperRole $role): array => [$role], ExternalServiceInspectionDeveloperRole::cases());
    }
}
