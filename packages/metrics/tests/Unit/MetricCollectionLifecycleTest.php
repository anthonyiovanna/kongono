<?php

declare(strict_types=1);

namespace Kongono\Metrics\Tests\Unit;

use Kongono\Metrics\Exception\InvalidMetricValueException;
use Kongono\Metrics\Exception\MetricLifecycleException;
use Kongono\Metrics\MetricCollectionState;
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
use Kongono\Observability\ObservationRecorder;
use Kongono\Observability\SignalAttribute;
use Kongono\Observability\SignalIdentity;
use PHPUnit\Framework\TestCase;

final class MetricCollectionLifecycleTest extends TestCase
{
    public function testEmptyCollectClosesOnceAndRetireIsIdempotent(): void
    {
        $recorder = $this->createMock(ObservationRecorder::class);
        $recorder->expects(self::never())->method('record');
        $instrument = $this->instrument();
        $collection = (new MetricRuntime($recorder))->begin(ObservabilityContext::uncorrelated(), new MetricPlan([$instrument]));
        self::assertSame(MetricCollectionState::Active, $collection->state());
        $collection->collect();
        self::assertSame(MetricCollectionState::Collected, $collection->state());
        $collection->retire();
        self::assertSame(MetricCollectionState::Collected, $collection->state());
        foreach ([static fn() => $collection->collect(), static fn() => $collection->record($instrument, new MetricMeasurement(new MetricNumber(1), new AttributeSet([])))] as $closedCall) {
            try {
                $closedCall();
                self::fail('Expected closed lifecycle failure.');
            } catch (MetricLifecycleException) {
                self::addToAssertionCount(1);
            }
        }
    }

    public function testEqualButForeignInstrumentAndInvalidDimensionsFail(): void
    {
        $instrument = $this->instrument();
        $foreign = $this->instrument();
        $collection = (new MetricRuntime(self::createStub(ObservationRecorder::class)))->begin(ObservabilityContext::uncorrelated(), new MetricPlan([$instrument]));
        try {
            $collection->record($foreign, new MetricMeasurement(new MetricNumber(1), new AttributeSet([])));
            self::fail('Expected ownership failure.');
        } catch (MetricLifecycleException $exception) {
            self::assertSame('KGN-METRICS-1003', $exception->diagnosticId());
        }

        foreach ([
            new AttributeSet([]),
            new AttributeSet([new SignalAttribute(new AttributeKey('app.kind'), AttributeValue::text('unknown'))]),
            new AttributeSet([new SignalAttribute(new AttributeKey('app.kind'), AttributeValue::text('allowed')), new SignalAttribute(new AttributeKey('app.extra'), AttributeValue::text('extra'))]),
        ] as $dimensions) {
            try {
                $collection->record($instrument, new MetricMeasurement(new MetricNumber(1), $dimensions));
                self::fail('Expected dimension rejection.');
            } catch (InvalidMetricValueException) {
                self::addToAssertionCount(1);
            }
        }
        self::assertSame(0, $collection->activeSeriesCount());
        $collection->retire();
        $collection->retire();
        self::assertSame(MetricCollectionState::Retired, $collection->state());
    }

    public function testRetireReleasesContextPlanDimensionsAndAggregates(): void
    {
        $context = ObservabilityContext::uncorrelated();
        $instrument = $this->instrument();
        $plan = new MetricPlan([$instrument]);
        $dimensions = new AttributeSet([new SignalAttribute(new AttributeKey('app.kind'), AttributeValue::text('allowed'))]);
        $contextReference = \WeakReference::create($context);
        $planReference = \WeakReference::create($plan);
        $dimensionsReference = \WeakReference::create($dimensions);
        $collection = (new MetricRuntime(self::createStub(ObservationRecorder::class)))->begin($context, $plan);
        $collection->record($instrument, new MetricMeasurement(new MetricNumber(1), $dimensions));
        $collection->retire();
        unset($context, $plan, $dimensions, $instrument);
        gc_collect_cycles();
        self::assertNull($contextReference->get());
        self::assertNull($planReference->get());
        self::assertNull($dimensionsReference->get());
    }

    private function instrument(): MetricInstrument
    {
        return new MetricInstrument(
            new SignalIdentity('metric.lifecycle', 1),
            MetricInstrumentKind::Counter,
            new MetricUnit('items'),
            new MetricDimensionSchema([new MetricDimension(new AttributeKey('app.kind'), [AttributeValue::text('allowed')])]),
        );
    }
}
