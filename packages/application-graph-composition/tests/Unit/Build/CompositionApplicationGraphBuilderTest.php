<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphComposition\Tests\Unit\Build;

use Kongono\ApplicationGraphComposition\Build\CompositionApplicationGraphBuilder;
use Kongono\ApplicationGraphComposition\Tests\Unit\Projection\CompositionProjectionMappingTest;
use Kongono\ApplicationGraphContract\ContractVersion;
use PHPUnit\Framework\TestCase;

final class CompositionApplicationGraphBuilderTest extends TestCase
{
    public function testOneCallReturnsFreshExactPhaseElevenCFacts(): void
    {
        $builder = new CompositionApplicationGraphBuilder();
        $first = $builder->build(CompositionProjectionMappingTest::artifact());
        $second = $builder->build(CompositionProjectionMappingTest::artifact());
        self::assertSame(ContractVersion::Version1, $first->version());
        self::assertCount(6, $first->nodes());
        self::assertCount(5, $first->relations());
        self::assertNotSame($first, $second);
        self::assertNotSame($first->nodes()[0], $second->nodes()[0]);
    }
}
