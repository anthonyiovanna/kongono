<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphComposition\Tests\Unit\Projection;

use Kongono\ApplicationGraphComposition\Projection\CompositionIrProjection;
use Kongono\ApplicationGraphComposition\Projection\CompositionIrProjector;
use PHPUnit\Framework\TestCase;

final class CompositionProjectionIsolationTest extends TestCase
{
    public function testArraysAndRecordsAreFreshAcrossInterleavedCalls(): void
    {
        $empty = new CompositionIrProjection([], []);
        self::assertSame([], $empty->nodeRecords());
        self::assertSame([], $empty->relationRecords());
        $projector = new CompositionIrProjector();
        $first = $projector->project(CompositionProjectionMappingTest::artifact());
        $nested = $projector->project(CompositionProjectionMappingTest::artifact());
        self::assertNotSame($first->nodeRecords(), $nested->nodeRecords());
        self::assertNotSame($first->nodeRecords()[0], $nested->nodeRecords()[0]);
    }
}
