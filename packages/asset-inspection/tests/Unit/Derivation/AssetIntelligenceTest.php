<?php

declare(strict_types=1);

namespace Kongono\AssetInspection\Tests\Unit\Derivation;

use Kongono\ApplicationGraphIntelligence\Role\DeveloperIntelligenceRole as GraphRole;
use Kongono\AssetInspection\Derivation\AssetIntelligenceDeriver;
use Kongono\AssetInspection\Derivation\AssetIntelligenceView;
use Kongono\AssetInspection\Exception\InvalidAssetIntelligenceEvidenceException;
use Kongono\AssetInspection\Graph\AssetGraphEvidenceProjector;
use Kongono\AssetInspection\Inspection\AssetInspectionRequest;
use Kongono\AssetInspection\Inspection\AssetInspector;
use Kongono\AssetInspection\Role\AssetInspectionDeveloperRole;
use Kongono\AssetInspection\Tests\Support\AssetInspectionFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AssetIntelligenceTest extends TestCase
{
    /** @return iterable<string, array{AssetInspectionDeveloperRole, string}> */
    public static function roles(): iterable
    {
        yield 'chie' => [AssetInspectionDeveloperRole::Chie, 'explain'];
        yield 'hantei' => [AssetInspectionDeveloperRole::Hantei, 'verify'];
        yield 'oshie' => [AssetInspectionDeveloperRole::Oshie, 'teach'];
    }

    #[DataProvider('roles')]
    public function testRolesSelectExactPurposesWithoutExecution(AssetInspectionDeveloperRole $role, string $purpose): void
    {
        $view = (new AssetIntelligenceDeriver())->derive($this->inspection(), $role);

        self::assertSame($purpose, $view->purpose());
        self::assertSame(AssetIntelligenceView::STATEMENTS, $view->statements());
        self::assertSame(AssetIntelligenceView::LIMITATIONS, $view->limitations());
        self::assertStringContainsString('"role_execution":"not-performed"', $view->canonicalBytes());
        self::assertStringContainsString('"network_access":"not-performed"', $view->canonicalBytes());
    }

    public function testGraphIsOptionalAggregateOnlyAndAlwaysUnestablished(): void
    {
        $inspection = $this->inspection();
        $projector = new AssetGraphEvidenceProjector();
        $absent = $projector->project($inspection, AssetInspectionDeveloperRole::Chie);
        $supplied = $projector->project($inspection, AssetInspectionDeveloperRole::Chie, AssetInspectionFactory::graph());
        $bytes = json_encode($supplied->toArray(), JSON_THROW_ON_ERROR);

        self::assertFalse($absent->supplied());
        self::assertTrue($supplied->supplied());
        self::assertSame('not-established', $absent->relationship());
        self::assertSame('not-established', $supplied->relationship());
        self::assertStringContainsString('safe-aggregate-only', $bytes);
        self::assertStringNotContainsString('artifact_sha256', $bytes);
        self::assertStringNotContainsString('graph_identity', $bytes);
    }

    public function testGraphRoleMismatchFailsClosed(): void
    {
        $this->expectException(InvalidAssetIntelligenceEvidenceException::class);
        (new AssetGraphEvidenceProjector())->project(
            $this->inspection(),
            AssetInspectionDeveloperRole::Chie,
            AssetInspectionFactory::graph(GraphRole::Hantei),
        );
    }

    private function inspection(): \Kongono\AssetInspection\Inspection\AssetInspection
    {
        return (new AssetInspector())->inspect(new AssetInspectionRequest(AssetInspectionFactory::current()));
    }
}
