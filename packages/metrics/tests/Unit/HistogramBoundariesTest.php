<?php

declare(strict_types=1);

namespace Kongono\Metrics\Tests\Unit;

use Kongono\Metrics\Exception\InvalidMetricValueException;
use Kongono\Metrics\HistogramBoundaries;
use Kongono\Metrics\MetricNumber;
use PHPUnit\Framework\TestCase;

final class HistogramBoundariesTest extends TestCase
{
    public function testExactStrictlyIncreasingObjectsArePreserved(): void
    {
        $first = new MetricNumber(-1);
        $second = new MetricNumber(0.5);
        $third = new MetricNumber(10);
        $boundaries = new HistogramBoundaries([$first, $second, $third]);
        self::assertSame([$first, $second, $third], $boundaries->boundaries());
        self::assertSame(3, $boundaries->count());
        self::assertSame(15, (new HistogramBoundaries(array_map(static fn(int $value): MetricNumber => new MetricNumber($value), range(1, 15))))->count());
    }

    public function testBoundsAndNumericDuplicatesFail(): void
    {
        foreach ([[], [new MetricNumber(1), new MetricNumber(1.0)], [new MetricNumber(2), new MetricNumber(1)], array_map(static fn(int $value): MetricNumber => new MetricNumber($value), range(1, 16))] as $invalid) {
            try {
                new HistogramBoundaries($invalid);
                self::fail('Expected invalid boundaries.');
            } catch (InvalidMetricValueException $exception) {
                self::assertSame('KGN-METRICS-1001', $exception->diagnosticId());
            }
        }
    }
}
