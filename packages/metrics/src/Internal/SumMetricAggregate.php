<?php

declare(strict_types=1);

namespace Kongono\Metrics\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Metrics\Exception\MetricAggregationException;
use Kongono\Metrics\MetricNumber;

/** Retains only a bounded count and exact native sum. */
#[Api(ApiStatus::Internal)]
final class SumMetricAggregate implements MetricAggregate
{
    private int $measurementCount = 0;

    private MetricNumberAccumulator $accumulator;

    public function __construct()
    {
        $this->accumulator = new MetricNumberAccumulator();
    }

    public function record(MetricNumber $value): void
    {
        if (PHP_INT_MAX === $this->measurementCount) {
            throw MetricAggregationException::overflow();
        }

        $next = $this->accumulator->next($value);
        $this->accumulator->commit($next);
        ++$this->measurementCount;
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
        return null;
    }

    public function maximum(): ?MetricNumber
    {
        return null;
    }

    public function bucketCounts(): array
    {
        return [];
    }
}
