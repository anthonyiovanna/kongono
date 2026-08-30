<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphComposition\Tests\Unit\Projection;

use Kongono\ApplicationGraphBuild\Ir\GraphNodeRecord;
use Kongono\ApplicationGraphBuild\Ir\GraphRelationRecord;
use Kongono\ApplicationGraphComposition\Projection\CompositionIrProjector;
use PHPUnit\Framework\TestCase;

final class CompositionProjectionDeterminismTest extends TestCase
{
    public function testIdentitiesOrderingAndArtifactBoundProvenanceAreRepeatable(): void
    {
        $projector = new CompositionIrProjector();
        $first = $projector->project(CompositionProjectionMappingTest::artifact());
        $second = $projector->project(CompositionProjectionMappingTest::artifact());
        $firstNodeIds = array_map(static fn(GraphNodeRecord $record): string => $record->identity()->value(), $first->nodeRecords());
        $secondNodeIds = array_map(static fn(GraphNodeRecord $record): string => $record->identity()->value(), $second->nodeRecords());
        $firstRelationIds = array_map(static fn(GraphRelationRecord $record): string => $record->identity()->value(), $first->relationRecords());
        $secondRelationIds = array_map(static fn(GraphRelationRecord $record): string => $record->identity()->value(), $second->relationRecords());
        self::assertSame($firstNodeIds, $secondNodeIds);
        self::assertSame($firstRelationIds, $secondRelationIds);
        $sorted = $firstNodeIds;
        sort($sorted, SORT_STRING);
        self::assertSame($sorted, $firstNodeIds);
        self::assertNotSame($first->nodeRecords()[0], $second->nodeRecords()[0]);
        self::assertSame(CompositionProjectionMappingTest::artifact()->artifactSha256(), $first->nodeRecords()[0]->provenance()->sourceSha256());
    }
}
