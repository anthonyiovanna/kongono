<?php

declare(strict_types=1);

namespace Kongono\Metrics\Tests\Unit;

use Kongono\Metrics\Exception\MetricLifecycleException;
use Kongono\Metrics\Exception\MetricRecordingException;
use Kongono\Metrics\MetricCollection;
use Kongono\Metrics\MetricCollectionState;
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
use RuntimeException;

final class MetricRecordingFailureTest extends TestCase
{
    public function testRecorderFailureIsFinalPreservesCauseAndStopsLaterCalls(): void
    {
        $failure = new RuntimeException('protected-recorder-payload');
        $recorder = new class ($failure) implements ObservationRecorder {
            public int $calls = 0;
            public ?MetricCollection $source = null;
            public bool $sourceWasClosed = false;

            public function __construct(private readonly RuntimeException $failure) {}

            public function record(Observation $observation): void
            {
                ++$this->calls;
                $this->sourceWasClosed = $this->source?->state() === MetricCollectionState::Collected;
                throw $this->failure;
            }
        };
        $first = $this->instrument('metric.first');
        $second = $this->instrument('metric.second');
        $collection = (new MetricRuntime($recorder))->begin(ObservabilityContext::uncorrelated(), new MetricPlan([$second, $first]));
        $recorder->source = $collection;
        $collection->record($first, $this->measurement());
        $collection->record($second, $this->measurement());
        try {
            $collection->collect();
            self::fail('Expected recorder failure.');
        } catch (MetricRecordingException $exception) {
            self::assertSame($failure, $exception->getPrevious());
            self::assertStringNotContainsString('protected-recorder-payload', (string) $exception);
        }
        self::assertSame(1, $recorder->calls);
        self::assertTrue($recorder->sourceWasClosed);
        self::assertSame(MetricCollectionState::Collected, $collection->state());
        try {
            $collection->collect();
            self::fail('Expected no retry after recorder failure.');
        } catch (MetricLifecycleException) {
            self::assertSame(1, $recorder->calls);
        }
    }

    private function instrument(string $name): MetricInstrument
    {
        return new MetricInstrument(new SignalIdentity($name, 1), MetricInstrumentKind::Counter, new MetricUnit('items'), new MetricDimensionSchema([]));
    }

    private function measurement(): MetricMeasurement
    {
        return new MetricMeasurement(new MetricNumber(1), new AttributeSet([]));
    }
}
