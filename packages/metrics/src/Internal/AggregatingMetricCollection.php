<?php

declare(strict_types=1);

namespace Kongono\Metrics\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Metrics\Exception\InvalidMetricValueException;
use Kongono\Metrics\Exception\MetricLifecycleException;
use Kongono\Metrics\Exception\MetricRecordingException;
use Kongono\Metrics\MetricCollection;
use Kongono\Metrics\MetricCollectionState;
use Kongono\Metrics\MetricInstrument;
use Kongono\Metrics\MetricInstrumentKind;
use Kongono\Metrics\MetricMeasurement;
use Kongono\Metrics\MetricPlan;
use Kongono\Observability\AttributeSet;
use Kongono\Observability\ObservabilityContext;
use Kongono\Observability\ObservationRecorder;
use Kongono\Observability\SignalAttribute;
use Throwable;

/** Owns one isolated bounded aggregate map and releases it before recorder calls. */
#[Api(ApiStatus::Internal)]
final class AggregatingMetricCollection implements MetricCollection
{
    private MetricCollectionState $state = MetricCollectionState::Active;

    /** @var array<int, array<int, MetricSeries>> */
    private array $series = [];

    public function __construct(
        private ?ObservabilityContext $context,
        private ?MetricPlan $plan,
        private ?ObservationRecorder $recorder,
    ) {}

    public function state(): MetricCollectionState
    {
        return $this->state;
    }

    public function record(MetricInstrument $instrument, MetricMeasurement $measurement): void
    {
        $plan = $this->activePlan();
        $instrumentIndex = null;
        foreach ($plan->instruments() as $index => $owned) {
            if ($owned === $instrument) {
                $instrumentIndex = $index;
                break;
            }
        }

        if (null === $instrumentIndex) {
            throw MetricLifecycleException::ownership();
        }
        if ($instrument->kind() === MetricInstrumentKind::Counter && $measurement->value()->value() < 0) {
            throw InvalidMetricValueException::measurement();
        }

        [$seriesOrdinal, $dimensions] = $this->admitDimensions($instrument, $measurement);
        $series = $this->series[$instrumentIndex][$seriesOrdinal] ?? null;
        if (null === $series) {
            $series = new MetricSeries($dimensions, MetricAggregateFactory::create($instrument));
            $this->series[$instrumentIndex][$seriesOrdinal] = $series;
        }

        $series->aggregate()->record($measurement->value());
    }

    public function collect(): void
    {
        $plan = $this->activePlan();
        $context = $this->context ?? throw MetricLifecycleException::state();
        $recorder = $this->recorder ?? throw MetricLifecycleException::state();
        $observations = [];
        foreach ($plan->instruments() as $instrumentIndex => $instrument) {
            $instrumentSeries = $this->series[$instrumentIndex] ?? [];
            ksort($instrumentSeries, SORT_NUMERIC);
            foreach ($instrumentSeries as $series) {
                $observations[] = MetricObservationFactory::create($context, $instrument, $series);
            }
        }

        $this->release(MetricCollectionState::Collected);
        foreach ($observations as $observation) {
            try {
                $recorder->record($observation);
            } catch (Throwable $failure) {
                throw MetricRecordingException::failed($failure);
            }
        }
    }

    public function retire(): void
    {
        if ($this->state === MetricCollectionState::Active) {
            $this->release(MetricCollectionState::Retired);
        }
    }

    public function activeSeriesCount(): int
    {
        if ($this->state !== MetricCollectionState::Active) {
            return 0;
        }

        $count = 0;
        foreach ($this->series as $instrumentSeries) {
            $count += count($instrumentSeries);
        }

        return $count;
    }

    private function activePlan(): MetricPlan
    {
        if ($this->state !== MetricCollectionState::Active || null === $this->plan) {
            throw MetricLifecycleException::state();
        }

        return $this->plan;
    }

    /** @return array{int, AttributeSet} */
    private function admitDimensions(MetricInstrument $instrument, MetricMeasurement $measurement): array
    {
        $schema = $instrument->dimensions()->dimensions();
        $supplied = $measurement->dimensions();
        if ($supplied->count() !== count($schema)) {
            throw InvalidMetricValueException::measurement();
        }

        $ordinal = 0;
        $canonical = [];
        foreach ($schema as $dimension) {
            $value = $supplied->find($dimension->key());
            if (null === $value) {
                throw InvalidMetricValueException::measurement();
            }

            $valueOrdinal = null;
            foreach ($dimension->allowedValues() as $index => $allowed) {
                if ($allowed->equals($value)) {
                    $valueOrdinal = $index;
                    break;
                }
            }
            if (null === $valueOrdinal) {
                throw InvalidMetricValueException::measurement();
            }

            $ordinal = $ordinal * count($dimension->allowedValues()) + $valueOrdinal;
            $canonical[] = new SignalAttribute($dimension->key(), $value);
        }

        return [$ordinal, new AttributeSet($canonical)];
    }

    private function release(MetricCollectionState $state): void
    {
        $this->state = $state;
        $this->context = null;
        $this->plan = null;
        $this->recorder = null;
        $this->series = [];
    }
}
