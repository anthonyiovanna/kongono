<?php

declare(strict_types=1);

namespace Kongono\Metrics\Tests\Unit;

use Kongono\Metrics\MetricDimension;
use Kongono\Metrics\MetricDimensionSchema;
use Kongono\Metrics\MetricInstrument;
use Kongono\Metrics\MetricInstrumentKind;
use Kongono\Metrics\MetricMeasurement;
use Kongono\Metrics\MetricNumber;
use Kongono\Metrics\MetricPlan;
use Kongono\Metrics\MetricRuntime;
use Kongono\Metrics\MetricUnit;
use Kongono\Observability\AttributeKey;
use Kongono\Observability\AttributeSet;
use Kongono\Observability\AttributeValue;
use Kongono\Observability\ObservabilityContext;
use Kongono\Observability\Observation;
use Kongono\Observability\ObservationRecorder;
use Kongono\Observability\SignalAttribute;
use Kongono\Observability\SignalIdentity;
use PHPUnit\Framework\TestCase;

final class MetricCardinalityTest extends TestCase
{
    public function testExactlyTwoHundredFiftySixPotentialAndPopulatedSeriesRemainBounded(): void
    {
        $values = array_map(static fn(int $value): AttributeValue => AttributeValue::integer($value), range(0, 7));
        $left = new MetricDimension(new AttributeKey('app.left'), $values);
        $right = new MetricDimension(new AttributeKey('app.right'), $values);
        $schema = new MetricDimensionSchema([$right, $left]);
        $instruments = array_map(static fn(int $index): MetricInstrument => new MetricInstrument(new SignalIdentity('metric.cardinality.' . $index, 1), MetricInstrumentKind::Counter, new MetricUnit('items'), $schema), range(0, 3));
        $plan = new MetricPlan($instruments);
        self::assertSame(256, $plan->seriesCapacity());
        $recorder = new class implements ObservationRecorder {
            /** @var list<Observation> */
            public array $observations = [];
            public function record(Observation $observation): void
            {
                $this->observations[] = $observation;
            }
        };
        $collection = (new MetricRuntime($recorder))->begin(ObservabilityContext::uncorrelated(), $plan);
        foreach ($instruments as $instrument) {
            foreach ($values as $leftValue) {
                foreach ($values as $rightValue) {
                    $collection->record($instrument, new MetricMeasurement(new MetricNumber(1), new AttributeSet([
                        new SignalAttribute(new AttributeKey('app.right'), $rightValue),
                        new SignalAttribute(new AttributeKey('app.left'), $leftValue),
                    ])));
                }
            }
        }
        self::assertSame(256, $collection->activeSeriesCount());
        $collection->collect();
        self::assertCount(256, $recorder->observations);
        self::assertSame(0, $collection->activeSeriesCount());
    }
}
