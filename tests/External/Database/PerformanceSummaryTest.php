<?php

declare(strict_types=1);

namespace Kongono\Tests\External\Database;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PerformanceSummaryTest extends TestCase
{
    public function testDeterministicNearestRankAndStableKeyOrder(): void
    {
        $summary = PerformanceSummary::fromSamples([20, 1, 12, 4, 17, 9, 6, 15, 2, 19, 8, 13, 3, 18, 7, 14, 5, 16, 10, 11])->toArray();

        self::assertSame(['samples', 'min_ns', 'p50_ns', 'p95_ns', 'max_ns'], array_keys($summary));
        self::assertSame([
            'samples' => 20,
            'min_ns' => 1,
            'p50_ns' => 10,
            'p95_ns' => 19,
            'max_ns' => 20,
        ], $summary);
        self::assertLessThanOrEqual($summary['p50_ns'], $summary['min_ns']);
        self::assertLessThanOrEqual($summary['p95_ns'], $summary['p50_ns']);
        self::assertLessThanOrEqual($summary['max_ns'], $summary['p95_ns']);
    }

    /** @param list<mixed> $samples */
    #[DataProvider('invalidSamples')]
    public function testRejectsEmptyOrInvalidSamples(array $samples): void
    {
        $this->expectException(InvalidArgumentException::class);

        PerformanceSummary::fromSamples($samples);
    }

    /** @return iterable<string, array{list<mixed>}> */
    public static function invalidSamples(): iterable
    {
        yield 'empty' => [[]];
        yield 'zero' => [[1, 0, 2]];
        yield 'negative' => [[1, -1, 2]];
        yield 'float' => [[1, 2.5, 3]];
        yield 'text' => [[1, '2', 3]];
    }
}
