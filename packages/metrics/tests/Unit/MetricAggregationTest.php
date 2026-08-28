<?php

declare(strict_types=1);

namespace Kongono\Metrics\Tests\Unit;

use Kongono\Metrics\Exception\InvalidMetricValueException;
use Kongono\Metrics\Exception\MetricAggregationException;
use Kongono\Metrics\HistogramBoundaries;
use Kongono\Metrics\Internal\HistogramMetricAggregate;
use Kongono\Metrics\Internal\SumMetricAggregate;
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
use Kongono\Observability\ObservabilityContext;
use Kongono\Observability\Observation;
use Kongono\Observability\ObservationRecorder;
use Kongono\Observability\SignalIdentity;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class MetricAggregationTest extends TestCase
{
    public function testAllFourAggregationsProduceExactReservedEvidence(): void
    {
        $counter = $this->instrument('metric.counter', MetricInstrumentKind::Counter);
        $upDown = $this->instrument('metric.up_down', MetricInstrumentKind::UpDownCounter);
        $gauge = $this->instrument('metric.gauge', MetricInstrumentKind::Gauge);
        $histogram = $this->instrument('metric.histogram', MetricInstrumentKind::Histogram, new HistogramBoundaries([new MetricNumber(0), new MetricNumber(10)]));
        $recorder = $this->recorder();
        $collection = (new MetricRuntime($recorder))->begin(ObservabilityContext::uncorrelated(), new MetricPlan([$histogram, $gauge, $upDown, $counter]));

        foreach ([1, 2.5] as $value) {
            $collection->record($counter, $this->measurement($value));
        }
        foreach ([5, -2] as $value) {
            $collection->record($upDown, $this->measurement($value));
        }
        foreach ([1, 2.5, 3] as $value) {
            $collection->record($gauge, $this->measurement($value));
        }
        foreach ([-1, 0, 5, 10, 11] as $value) {
            $collection->record($histogram, $this->measurement($value));
        }
        $collection->collect();

        self::assertSame(['metric.counter', 'metric.gauge', 'metric.histogram', 'metric.up_down'], array_map(static fn(Observation $observation): string => $observation->identity()->name(), $recorder->observations));
        self::assertSame(3.5, $this->value($recorder->observations[0], 'metric.sum'));
        self::assertSame(3, $this->value($recorder->observations[1], 'metric.measurement_count'));
        self::assertSame(3, $this->value($recorder->observations[1], 'metric.last'));
        $histogramObservation = $recorder->observations[2];
        self::assertSame(5, $this->value($histogramObservation, 'metric.measurement_count'));
        self::assertSame(25, $this->value($histogramObservation, 'metric.sum'));
        self::assertSame(-1, $this->value($histogramObservation, 'metric.min'));
        self::assertSame(11, $this->value($histogramObservation, 'metric.max'));
        self::assertSame([2, 2, 1], [
            $this->value($histogramObservation, 'metric.bucket.00.count'),
            $this->value($histogramObservation, 'metric.bucket.01.count'),
            $this->value($histogramObservation, 'metric.bucket.02.count'),
        ]);
        self::assertSame(3, $this->value($recorder->observations[3], 'metric.sum'));
    }

    public function testCounterRejectsNegativeAndFailedSumDoesNotMutate(): void
    {
        $counter = $this->instrument('metric.counter', MetricInstrumentKind::Counter);
        $recorder = $this->recorder();
        $collection = (new MetricRuntime($recorder))->begin(ObservabilityContext::uncorrelated(), new MetricPlan([$counter]));
        try {
            $collection->record($counter, $this->measurement(-1));
            self::fail('Expected negative Counter rejection.');
        } catch (InvalidMetricValueException) {
            self::assertSame(0, $collection->activeSeriesCount());
        }
        $collection->record($counter, $this->measurement(PHP_INT_MAX));
        try {
            $collection->record($counter, $this->measurement(1));
            self::fail('Expected integer overflow.');
        } catch (MetricAggregationException) {
            self::assertSame(1, $collection->activeSeriesCount());
        }
        $collection->collect();
        self::assertSame(1, $this->value($recorder->observations[0], 'metric.measurement_count'));
        self::assertSame(PHP_INT_MAX, $this->value($recorder->observations[0], 'metric.sum'));
    }

    public function testFloatCountAndBucketOverflowsAreVisibleBeforeMutation(): void
    {
        $sum = new SumMetricAggregate();
        $sum->record(new MetricNumber(PHP_FLOAT_MAX));
        $this->expectException(MetricAggregationException::class);
        $sum->record(new MetricNumber(PHP_FLOAT_MAX));
    }

    public function testCountAndBucketOverflowGuardsAreExplicit(): void
    {
        $sum = new SumMetricAggregate();
        (new ReflectionProperty($sum, 'measurementCount'))->setValue($sum, PHP_INT_MAX);
        try {
            $sum->record(new MetricNumber(1));
            self::fail('Expected count overflow.');
        } catch (MetricAggregationException) {
            self::assertSame(PHP_INT_MAX, $sum->measurementCount());
        }

        $histogram = new HistogramMetricAggregate(new HistogramBoundaries([new MetricNumber(0)]));
        (new ReflectionProperty($histogram, 'bucketCounts'))->setValue($histogram, [PHP_INT_MAX, 0]);
        try {
            $histogram->record(new MetricNumber(0));
            self::fail('Expected bucket overflow.');
        } catch (MetricAggregationException) {
            self::assertSame([PHP_INT_MAX, 0], $histogram->bucketCounts());
            self::assertSame(0, $histogram->measurementCount());
        }
    }

    private function instrument(string $name, MetricInstrumentKind $kind, ?HistogramBoundaries $boundaries = null): MetricInstrument
    {
        return new MetricInstrument(new SignalIdentity($name, 1), $kind, new MetricUnit('items'), new MetricDimensionSchema([]), $boundaries);
    }

    private function measurement(int|float $value): MetricMeasurement
    {
        return new MetricMeasurement(new MetricNumber($value), new AttributeSet([]));
    }

    private function value(Observation $observation, string $key): bool|int|float|string
    {
        return $observation->attributes()->find(new AttributeKey($key))?->value() ?? self::fail('Missing attribute ' . $key);
    }

    /** @return ObservationRecorder&object{observations: list<Observation>} */
    private function recorder(): object
    {
        return new class implements ObservationRecorder {
            /** @var list<Observation> */
            public array $observations = [];

            public function record(Observation $observation): void
            {
                $this->observations[] = $observation;
            }
        };
    }
}
