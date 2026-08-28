<?php

declare(strict_types=1);

namespace Kongono\Metrics\Tests\Unit;

use Kongono\Metrics\MetricMeasurement;
use Kongono\Metrics\MetricNumber;
use Kongono\Observability\AttributeSet;
use PHPUnit\Framework\TestCase;

final class MetricMeasurementTest extends TestCase
{
    public function testExactValueAndDimensionObjectsAreRetained(): void
    {
        $value = new MetricNumber(7);
        $dimensions = new AttributeSet([]);
        $measurement = new MetricMeasurement($value, $dimensions);
        self::assertSame($value, $measurement->value());
        self::assertSame($dimensions, $measurement->dimensions());
    }
}
