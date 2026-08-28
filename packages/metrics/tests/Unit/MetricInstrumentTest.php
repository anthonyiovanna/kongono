<?php

declare(strict_types=1);

namespace Kongono\Metrics\Tests\Unit;

use Kongono\Metrics\Exception\InvalidMetricSchemaException;
use Kongono\Metrics\HistogramBoundaries;
use Kongono\Metrics\MetricDimensionSchema;
use Kongono\Metrics\MetricInstrument;
use Kongono\Metrics\MetricInstrumentKind;
use Kongono\Metrics\MetricNumber;
use Kongono\Metrics\MetricUnit;
use Kongono\Observability\SignalIdentity;
use PHPUnit\Framework\TestCase;

final class MetricInstrumentTest extends TestCase
{
    public function testExactInstrumentShapeAndEnumsAreFixed(): void
    {
        self::assertSame(['counter', 'up_down_counter', 'gauge', 'histogram'], array_column(MetricInstrumentKind::cases(), 'value'));
        $identity = new SignalIdentity('metric.requests', 2);
        $unit = new MetricUnit('requests');
        $dimensions = new MetricDimensionSchema([]);
        $boundaries = new HistogramBoundaries([new MetricNumber(1)]);
        $instrument = new MetricInstrument($identity, MetricInstrumentKind::Histogram, $unit, $dimensions, $boundaries);
        self::assertSame($identity, $instrument->identity());
        self::assertSame(MetricInstrumentKind::Histogram, $instrument->kind());
        self::assertSame($unit, $instrument->unit());
        self::assertSame($dimensions, $instrument->dimensions());
        self::assertSame($boundaries, $instrument->boundaries());
    }

    public function testHistogramBoundaryContractIsExact(): void
    {
        $identity = new SignalIdentity('metric.invalid', 1);
        $unit = new MetricUnit('items');
        $dimensions = new MetricDimensionSchema([]);
        foreach ([
            static fn(): MetricInstrument => new MetricInstrument($identity, MetricInstrumentKind::Histogram, $unit, $dimensions),
            static fn(): MetricInstrument => new MetricInstrument($identity, MetricInstrumentKind::Counter, $unit, $dimensions, new HistogramBoundaries([new MetricNumber(1)])),
        ] as $invalid) {
            try {
                $invalid();
                self::fail('Expected invalid instrument schema.');
            } catch (InvalidMetricSchemaException) {
                self::addToAssertionCount(1);
            }
        }
    }
}
