<?php

declare(strict_types=1);

namespace Kongono\Metrics\Tests\Unit;

use Kongono\Metrics\Exception\MetricLifecycleException;
use Kongono\Metrics\MetricCollection;
use Kongono\Metrics\MetricDimensionSchema;
use Kongono\Metrics\MetricInstrument;
use Kongono\Metrics\MetricInstrumentKind;
use Kongono\Metrics\MetricMeasurement;
use Kongono\Metrics\MetricNumber;
use Kongono\Metrics\MetricPlan;
use Kongono\Metrics\MetricRuntime;
use Kongono\Metrics\MetricUnit;
use Kongono\Observability\AttributeSet;
use Kongono\Observability\ObservabilityContext;
use Kongono\Observability\Observation;
use Kongono\Observability\ObservationRecorder;
use Kongono\Observability\SignalIdentity;
use PHPUnit\Framework\TestCase;

final class MetricIsolationTest extends TestCase
{
    public function testSequentialAndOverlappingCollectionsShareNoAggregateState(): void
    {
        $recorder = new class implements ObservationRecorder {
            /** @var list<Observation> */
            public array $observations = [];
            public function record(Observation $observation): void
            {
                $this->observations[] = $observation;
            }
        };
        $runtime = new MetricRuntime($recorder);
        $instrument = $this->instrument('metric.isolation');
        $plan = new MetricPlan([$instrument]);
        $first = $runtime->begin(ObservabilityContext::uncorrelated(), $plan);
        $second = $runtime->begin(ObservabilityContext::uncorrelated(), $plan);
        $first->record($instrument, $this->measurement(1));
        $second->record($instrument, $this->measurement(2));
        $second->record($instrument, $this->measurement(3));
        self::assertSame(1, $first->activeSeriesCount());
        self::assertSame(1, $second->activeSeriesCount());
        $first->collect();
        $second->collect();
        self::assertCount(2, $recorder->observations);

        $third = $runtime->begin(ObservabilityContext::uncorrelated(), $plan);
        self::assertSame(0, $third->activeSeriesCount());
        $third->retire();
    }

    public function testRecorderCanReenterRuntimeWhileSourceIsClosed(): void
    {
        $instrument = $this->instrument('metric.reentrant');
        $plan = new MetricPlan([$instrument]);
        $recorder = new class implements ObservationRecorder {
            public ?MetricRuntime $runtime = null;
            public ?MetricCollection $source = null;
            public ?MetricPlan $plan = null;
            public int $calls = 0;
            public bool $sourceRejectedReentry = false;

            public function record(Observation $observation): void
            {
                ++$this->calls;
                try {
                    $this->source?->collect();
                } catch (MetricLifecycleException) {
                    $this->sourceRejectedReentry = true;
                }
                $nested = $this->runtime?->begin(ObservabilityContext::uncorrelated(), $this->plan ?? throw new \LogicException());
                $nested?->collect();
            }
        };
        $runtime = new MetricRuntime($recorder);
        $recorder->runtime = $runtime;
        $recorder->plan = $plan;
        $source = $runtime->begin(ObservabilityContext::uncorrelated(), $plan);
        $recorder->source = $source;
        $source->record($instrument, $this->measurement(1));
        $source->collect();
        self::assertSame(1, $recorder->calls);
        self::assertTrue($recorder->sourceRejectedReentry);
    }

    private function instrument(string $name): MetricInstrument
    {
        return new MetricInstrument(new SignalIdentity($name, 1), MetricInstrumentKind::Counter, new MetricUnit('items'), new MetricDimensionSchema([]));
    }

    private function measurement(int $value): MetricMeasurement
    {
        return new MetricMeasurement(new MetricNumber($value), new AttributeSet([]));
    }
}
