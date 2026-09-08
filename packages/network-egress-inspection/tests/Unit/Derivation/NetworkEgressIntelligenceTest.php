<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressInspection\Tests\Unit\Derivation;

use Kongono\ApplicationGraphIntelligence\Role\DeveloperIntelligenceRole as GraphRole;
use Kongono\NetworkEgressInspection\Derivation\NetworkEgressIntelligenceDeriver;
use Kongono\NetworkEgressInspection\Derivation\NetworkEgressIntelligenceView;
use Kongono\NetworkEgressInspection\Exception\InvalidNetworkEgressIntelligenceEvidenceException;
use Kongono\NetworkEgressInspection\Graph\NetworkEgressGraphEvidence;
use Kongono\NetworkEgressInspection\Inspection\NetworkEgressInspectionRequest;
use Kongono\NetworkEgressInspection\Inspection\NetworkEgressInspector;
use Kongono\NetworkEgressInspection\Role\NetworkEgressInspectionDeveloperRole;
use Kongono\NetworkEgressInspection\Tests\Support\NetworkEgressInspectionFactory as Fixture;
use Kongono\NetworkEgressInspection\Verification\NetworkEgressIntelligenceVerdict;
use Kongono\NetworkEgressInspection\Verification\NetworkEgressIntelligenceVerifier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NetworkEgressIntelligenceTest extends TestCase
{
    #[DataProvider('roles')]
    public function testAllRolesDeriveDeterministicallyAndVerifyIndependently(NetworkEgressInspectionDeveloperRole $role): void
    {
        $profile = Fixture::profile();
        $inspection = (new NetworkEgressInspector())->inspect(new NetworkEgressInspectionRequest($profile, Fixture::wiring($profile)));
        $deriver = new NetworkEgressIntelligenceDeriver();
        $first = $deriver->derive($inspection, $role);
        $second = $deriver->derive($inspection, $role);

        self::assertSame($role->purpose(), $first->purpose());
        self::assertSame($first->canonicalBytes(), $second->canonicalBytes());
        self::assertSame(NetworkEgressIntelligenceView::statementsFor($role), $first->statements());
        self::assertSame('not-performed', $first->roleExecution());
        self::assertSame('not-performed', $first->networkAccess());
        self::assertSame('not-performed', $first->authorityEvaluation());
        self::assertSame('not-performed', $first->repair());
        self::assertSame('not-performed', $first->replay());
        self::assertSame('not-performed', $first->certification());
        self::assertSame('none', $first->hiddenDataAccess());
        self::assertSame('evidence-only', $first->authority());
        self::assertSame(NetworkEgressIntelligenceVerdict::Pass, (new NetworkEgressIntelligenceVerifier())->verify($inspection, $first)->verdict());
    }

    public function testMatchingGraphCopiesOnlySafeAggregatesAndMismatchedRoleFailsClosed(): void
    {
        $profile = Fixture::profile();
        $inspection = (new NetworkEgressInspector())->inspect(new NetworkEgressInspectionRequest($profile, Fixture::wiring($profile)));
        $view = (new NetworkEgressIntelligenceDeriver())->derive(
            $inspection,
            NetworkEgressInspectionDeveloperRole::Chie,
            Fixture::graphView(GraphRole::Chie),
        );

        self::assertTrue($view->graphEvidence()->supplied());
        self::assertSame(NetworkEgressGraphEvidence::RELATIONSHIP, $view->graphEvidence()->relationship());
        $graph = $view->graphEvidence()->toArray()['graph'];
        self::assertIsArray($graph);
        self::assertSame(10, $graph['node_count']);
        self::assertSame(NetworkEgressIntelligenceVerdict::Pass, (new NetworkEgressIntelligenceVerifier())->verify($inspection, $view, Fixture::graphView(GraphRole::Chie))->verdict());

        $this->expectException(InvalidNetworkEgressIntelligenceEvidenceException::class);
        (new NetworkEgressIntelligenceDeriver())->derive(
            $inspection,
            NetworkEgressInspectionDeveloperRole::Chie,
            Fixture::graphView(GraphRole::Hantei),
        );
    }

    public function testVerifierRejectsAlteredDigestWithoutRepairOrDerivation(): void
    {
        $profile = Fixture::profile();
        $inspection = (new NetworkEgressInspector())->inspect(new NetworkEgressInspectionRequest($profile, Fixture::wiring($profile)));
        $role = NetworkEgressInspectionDeveloperRole::Hantei;
        $candidate = new NetworkEgressIntelligenceView(
            $role,
            $role->purpose(),
            $inspection,
            new NetworkEgressGraphEvidence($inspection->identity(), $role, false),
            ['inspection-bindings', 'optional-runtime-evidence', 'graph-aggregates', 'limitations'],
            NetworkEgressIntelligenceView::statementsFor($role),
            serializationSha256: str_repeat('f', 64),
        );

        $verification = (new NetworkEgressIntelligenceVerifier())->verify($inspection, $candidate);
        self::assertSame(NetworkEgressIntelligenceVerdict::Fail, $verification->verdict());
        self::assertSame(['deterministic-serialization-failed'], $verification->failureTokens());
        self::assertSame('not-performed', $candidate->repair());
    }

    public function testCandidateCannotExpandOrderedEvidenceGroups(): void
    {
        $profile = Fixture::profile();
        $inspection = (new NetworkEgressInspector())->inspect(new NetworkEgressInspectionRequest($profile, Fixture::wiring($profile)));
        $role = NetworkEgressInspectionDeveloperRole::Oshie;

        $this->expectException(InvalidNetworkEgressIntelligenceEvidenceException::class);
        new NetworkEgressIntelligenceView(
            $role,
            $role->purpose(),
            $inspection,
            new NetworkEgressGraphEvidence($inspection->identity(), $role, false),
            ['inspection-bindings', 'hidden-data', 'limitations'],
            NetworkEgressIntelligenceView::statementsFor($role),
        );
    }

    /** @return list<array{NetworkEgressInspectionDeveloperRole}> */
    public static function roles(): array
    {
        return array_map(static fn(NetworkEgressInspectionDeveloperRole $role): array => [$role], NetworkEgressInspectionDeveloperRole::cases());
    }
}
