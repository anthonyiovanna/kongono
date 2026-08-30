<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphIntelligence\Tests\Unit\Derivation;

use Kongono\ApplicationGraphArtifact\Identity\GraphIdentity;
use Kongono\ApplicationGraphArtifact\Inspection\ApplicationGraphArtifactInspection;
use Kongono\ApplicationGraphContract\ContractVersion;
use Kongono\ApplicationGraphIntelligence\Derivation\ApplicationGraphIntelligenceDeriver;
use Kongono\ApplicationGraphIntelligence\Role\DeveloperIntelligenceRole;
use PHPUnit\Framework\TestCase;

final class ApplicationGraphIntelligenceDeriverTest extends TestCase
{
    public function testDerivationCopiesOnlyInspectionAggregatesAndRolesDifferOnlyByRole(): void
    {
        $inspection = self::inspection();
        $deriver = new ApplicationGraphIntelligenceDeriver();
        $views = array_map(
            static fn(DeveloperIntelligenceRole $role) => $deriver->derive($inspection, $role),
            DeveloperIntelligenceRole::cases(),
        );

        $neutral = [];
        foreach ($views as $position => $view) {
            self::assertNotSame($view, $deriver->derive($inspection, DeveloperIntelligenceRole::cases()[$position]));
            self::assertSame($inspection->artifactSha256(), $view->artifactSha256());
            self::assertSame($inspection->graphIdentity(), $view->graphIdentity());
            self::assertSame($inspection->contractVersion(), $view->contractVersion());
            self::assertSame($inspection->nodeCount(), $view->nodeCount());
            self::assertSame($inspection->relationCount(), $view->relationCount());
            $array = $view->toArray();
            unset($array['role']);
            $neutral[] = $array;
        }
        self::assertSame($neutral[0], $neutral[1]);
        self::assertSame($neutral[0], $neutral[2]);
        self::assertSame(['chie', 'hantei', 'oshie'], array_map(static fn($view): string => $view->role()->value, $views));
    }

    public function testZeroCountAndInterleavedDerivationRemainDeterministic(): void
    {
        $zero = new ApplicationGraphArtifactInspection(
            str_repeat('0', 64),
            new GraphIdentity(str_repeat('1', 64)),
            ContractVersion::Version1,
            0,
            0,
            0,
            0,
            0,
            0,
            0,
            0,
            0,
            0,
        );
        $deriver = new ApplicationGraphIntelligenceDeriver();
        $first = $deriver->derive($zero, DeveloperIntelligenceRole::Oshie)->toArray();
        $deriver->derive(self::inspection(), DeveloperIntelligenceRole::Chie);
        self::assertSame($first, $deriver->derive($zero, DeveloperIntelligenceRole::Oshie)->toArray());
        $nodeCounts = $first['node_type_counts'];
        $relationCounts = $first['relation_type_counts'];
        self::assertIsArray($nodeCounts);
        self::assertIsArray($relationCounts);
        self::assertSame([0, 0, 0, 0], array_values($nodeCounts));
        self::assertSame([0, 0, 0, 0], array_values($relationCounts));
    }

    private static function inspection(): ApplicationGraphArtifactInspection
    {
        return new ApplicationGraphArtifactInspection(
            str_repeat('a', 64),
            new GraphIdentity(str_repeat('b', 64)),
            ContractVersion::Version1,
            10,
            26,
            1,
            2,
            3,
            4,
            5,
            6,
            7,
            8,
        );
    }
}
