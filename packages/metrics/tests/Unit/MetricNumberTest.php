<?php

declare(strict_types=1);

namespace Kongono\Metrics\Tests\Unit;

use Kongono\Metrics\Exception\InvalidMetricValueException;
use Kongono\Metrics\MetricNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MetricNumberTest extends TestCase
{
    public function testExactNativeTypeAndValueArePreserved(): void
    {
        $integer = new MetricNumber(1);
        $float = new MetricNumber(1.0);
        self::assertSame(1, $integer->value());
        self::assertSame(1.0, $float->value());
        self::assertTrue($integer->isInteger());
        self::assertFalse($integer->isFloat());
        self::assertTrue($float->isFloat());
        self::assertFalse($float->isInteger());
        self::assertTrue($integer->equals(new MetricNumber(1)));
        self::assertFalse($integer->equals($float));
        self::assertTrue((new MetricNumber(-0.0))->equals(new MetricNumber(0.0)));
    }

    #[DataProvider('nonFiniteValues')]
    public function testNonFiniteFloatsFailWithoutDisclosure(float $value): void
    {
        try {
            new MetricNumber($value);
            self::fail('Expected invalid metric value.');
        } catch (InvalidMetricValueException $exception) {
            self::assertSame('KGN-METRICS-1001', $exception->diagnosticId());
            self::assertSame('KGN-METRICS-1001: Invalid metric value', (string) $exception);
        }
    }

    /** @return iterable<string, array{float}> */
    public static function nonFiniteValues(): iterable
    {
        yield 'positive infinity' => [INF];
        yield 'negative infinity' => [-INF];
        yield 'not a number' => [NAN];
    }
}
