<?php

declare(strict_types=1);

namespace Kongono\Metrics\Tests\Unit;

use Kongono\Metrics\Exception\InvalidMetricSchemaException;
use Kongono\Metrics\MetricDimension;
use Kongono\Metrics\MetricDimensionSchema;
use Kongono\Metrics\MetricInstrument;
use Kongono\Metrics\MetricInstrumentKind;
use Kongono\Metrics\MetricPlan;
use Kongono\Metrics\MetricUnit;
use Kongono\Observability\AttributeKey;
use Kongono\Observability\AttributeValue;
use Kongono\Observability\SignalIdentity;
use PHPUnit\Framework\TestCase;

final class MetricPlanTest extends TestCase
{
    public function testCanonicalOrderCapacityAndExactOwnershipArePreserved(): void
    {
        $later = $this->instrument('metric.z', 1, 1);
        $versionTwo = $this->instrument('metric.a', 2, 2);
        $versionOne = $this->instrument('metric.a', 1, 4);
        $plan = new MetricPlan([$later, $versionTwo, $versionOne]);
        self::assertSame([$versionOne, $versionTwo, $later], $plan->instruments());
        self::assertSame(7, $plan->seriesCapacity());
    }

    public function testPlanBoundsIdentityUniquenessAndCapacityAreHard(): void
    {
        $duplicate = $this->instrument('metric.duplicate', 1, 1);
        $sixteen = array_map(fn(int $index): MetricInstrument => $this->instrument('metric.i' . $index, 1, 1), range(0, 15));
        self::assertCount(16, (new MetricPlan($sixteen))->instruments());
        foreach ([
            static fn(): MetricPlan => new MetricPlan([]),
            static fn(): MetricPlan => new MetricPlan([$duplicate, $duplicate]),
            fn(): MetricPlan => new MetricPlan(array_map(fn(int $index): MetricInstrument => $this->instrument('metric.too_many' . $index, 1, 1), range(0, 16))),
            fn(): MetricPlan => new MetricPlan(array_map(fn(int $index): MetricInstrument => $this->instrument('metric.capacity' . $index, 1, 64), range(0, 4))),
        ] as $invalid) {
            try {
                $invalid();
                self::fail('Expected invalid plan.');
            } catch (InvalidMetricSchemaException) {
                self::addToAssertionCount(1);
            }
        }
    }

    private function instrument(string $name, int $version, int $capacity): MetricInstrument
    {
        if (1 === $capacity) {
            $schema = new MetricDimensionSchema([]);
        } elseif (64 === $capacity) {
            $values = array_map(static fn(int $value): AttributeValue => AttributeValue::integer($value), range(1, 8));
            $schema = new MetricDimensionSchema([
                new MetricDimension(new AttributeKey('app.dimension.a'), $values),
                new MetricDimension(new AttributeKey('app.dimension.b'), $values),
            ]);
        } else {
            $values = array_map(static fn(int $value): AttributeValue => AttributeValue::integer($value), range(1, $capacity));
            $schema = new MetricDimensionSchema([new MetricDimension(new AttributeKey('app.dimension'), $values)]);
        }

        return new MetricInstrument(new SignalIdentity($name, $version), MetricInstrumentKind::Counter, new MetricUnit('items'), $schema);
    }
}
