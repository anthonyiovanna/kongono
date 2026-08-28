<?php

declare(strict_types=1);

namespace Kongono\Metrics\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Metrics\Exception\MetricAggregationException;
use Kongono\Metrics\HistogramBoundaries;
use Kongono\Metrics\MetricNumber;

/** Retains bounded fixed-bucket count, sum, minimum, and maximum state. */
#[Api(ApiStatus::Internal)]
final class HistogramMetricAggregate implements MetricAggregate
{
    private int $measurementCount = 0;

    private MetricNumberAccumulator $accumulator;

    private ?MetricNumber $minimum = null;

    private ?MetricNumber $maximum = null;

    /** @var list<int> */
    private array $bucketCounts;

    public function __construct(private readonly HistogramBoundaries $boundaries)
    {
        $this->accumulator = new MetricNumberAccumulator();
        $this->bucketCounts = array_fill(0, $boundaries->count() + 1, 0);
    }

    public function record(MetricNumber $value): void
    {
        if (PHP_INT_MAX === $this->measurementCount) {
            throw MetricAggregationException::overflow();
        }

        $bucket = $this->bucketFor($value);
        if (PHP_INT_MAX === $this->bucketCounts[$bucket]) {
            throw MetricAggregationException::overflow();
        }

        $nextSum = $this->accumulator->next($value);
        $nextBuckets = $this->bucketCounts;
        $nextBuckets[$bucket] = $nextBuckets[$bucket] + 1;
        $this->accumulator->commit($nextSum);
        ++$this->measurementCount;
        $this->bucketCounts = array_values($nextBuckets);
        if (null === $this->minimum || $value->value() < $this->minimum->value()) {
            $this->minimum = $value;
        }
        if (null === $this->maximum || $value->value() > $this->maximum->value()) {
            $this->maximum = $value;
        }
    }

    public function measurementCount(): int
    {
        return $this->measurementCount;
    }

    public function sum(): MetricNumber
    {
        return $this->accumulator->metricNumber();
    }

    public function last(): ?MetricNumber
    {
        return null;
    }

    public function minimum(): ?MetricNumber
    {
        return $this->minimum;
    }

    public function maximum(): ?MetricNumber
    {
        return $this->maximum;
    }

    public function bucketCounts(): array
    {
        return $this->bucketCounts;
    }

    private function bucketFor(MetricNumber $value): int
    {
        foreach ($this->boundaries->boundaries() as $index => $boundary) {
            if ($value->value() <= $boundary->value()) {
                return $index;
            }
        }

        return $this->boundaries->count();
    }
}
