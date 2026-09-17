<?php

declare(strict_types=1);

namespace Kongono\CacheInspection\Tests\Unit\Derivation;

use Kongono\ApplicationGraphIntelligence\Role\DeveloperIntelligenceRole as GraphRole;
use Kongono\CacheInspection\Derivation\CacheIntelligenceDeriver;
use Kongono\CacheInspection\Derivation\CacheIntelligenceView;
use Kongono\CacheInspection\Exception\InvalidCacheIntelligenceEvidenceException;
use Kongono\CacheInspection\Graph\CacheGraphEvidence;
use Kongono\CacheInspection\Graph\CacheGraphEvidenceProjector;
use Kongono\CacheInspection\Inspection\CacheInspectionRequest;
use Kongono\CacheInspection\Inspection\CacheInspector;
use Kongono\CacheInspection\Role\CacheInspectionDeveloperRole;
use Kongono\CacheInspection\Tests\Support\CacheInspectionFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CacheIntelligenceTest extends TestCase
{
    /** @return iterable<string, array{CacheInspectionDeveloperRole, string}> */
    public static function roles(): iterable
    {
        yield 'chie' => [CacheInspectionDeveloperRole::Chie, 'explain'];
        yield 'hantei' => [CacheInspectionDeveloperRole::Hantei, 'verify'];
        yield 'oshie' => [CacheInspectionDeveloperRole::Oshie, 'teach'];
    }

    #[DataProvider('roles')]
    public function testRolesSelectExactPurposesWithoutExecution(CacheInspectionDeveloperRole $role, string $purpose): void
    {
        $view = (new CacheIntelligenceDeriver())->derive($this->inspection(), $role);

        self::assertSame($purpose, $view->purpose());
        self::assertSame(CacheIntelligenceView::STATEMENTS, $view->statements());
        self::assertSame(CacheIntelligenceView::LIMITATIONS, $view->limitations());
        self::assertStringContainsString('"role_execution":"not-performed"', $view->canonicalBytes());
        self::assertStringContainsString('"backend_access":"not-performed"', $view->canonicalBytes());
    }

    public function testGraphIsOptionalAggregateOnlyAndAlwaysUnestablished(): void
    {
        $inspection = $this->inspection();
        $projector = new CacheGraphEvidenceProjector();
        $absent = $projector->project($inspection, CacheInspectionDeveloperRole::Chie);
        $supplied = $projector->project($inspection, CacheInspectionDeveloperRole::Chie, CacheInspectionFactory::graph());
        $bytes = json_encode($supplied->toArray(), JSON_THROW_ON_ERROR);

        self::assertFalse($absent->supplied());
        self::assertTrue($supplied->supplied());
        self::assertSame('not-established', $absent->relationship());
        self::assertSame('not-established', $supplied->relationship());
        self::assertStringContainsString('safe-aggregate-only', $bytes);
        self::assertStringNotContainsString('artifact_sha256', $bytes);
        self::assertStringNotContainsString('graph_identity', $bytes);
    }

    public function testGraphRoleMismatchAndTamperedViewFailClosed(): void
    {
        $this->expectException(InvalidCacheIntelligenceEvidenceException::class);
        (new CacheGraphEvidenceProjector())->project(
            $this->inspection(),
            CacheInspectionDeveloperRole::Chie,
            CacheInspectionFactory::graph(GraphRole::Hantei),
        );
    }

    public function testPurposeStatementsSerializationAndGraphRelationshipTamperingFailClosed(): void
    {
        $inspection = $this->inspection();
        $role = CacheInspectionDeveloperRole::Chie;
        $graph = (new CacheGraphEvidenceProjector())->project($inspection, $role);
        $cases = [
            static fn() => new CacheIntelligenceView($role, 'teach', $inspection, $graph),
            static fn() => new CacheIntelligenceView($role, $role->purpose(), $inspection, $graph, ['tampered']),
            static fn() => new CacheIntelligenceView($role, $role->purpose(), $inspection, $graph, serializationSha256: str_repeat('f', 64)),
            static fn() => new CacheGraphEvidence($inspection->identity(), $role, false, relationship: 'established'),
        ];

        foreach ($cases as $case) {
            try {
                $case();
                self::fail('Tampered derived evidence must fail.');
            } catch (InvalidCacheIntelligenceEvidenceException $failure) {
                self::assertSame('KGN-CACHE-INSPECTION-2053', $failure->diagnosticId());
            }
        }
    }

    private function inspection(): \Kongono\CacheInspection\Inspection\CacheInspection
    {
        $current = CacheInspectionFactory::current();

        return (new CacheInspector())->inspect(new CacheInspectionRequest($current, CacheInspectionFactory::runtime($current)));
    }
}
