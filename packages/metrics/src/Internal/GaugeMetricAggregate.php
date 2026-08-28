<?php

declare(strict_types=1);

namespace Kongono\Metrics\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Metrics\Exception\MetricAggregationException;
use Kongono\Metrics\MetricNumber;

/** Retains only a bounded count and the last exact value by call order. */
#[Api(ApiStatus::Internal)]
final class GaugeMetricAggregate implements MetricAggregate
{
    private int $measurementCount = 0;

    private ?MetricNumber $last = null;

    public function record(MetricNumber $value): void
    {
        if (PHP_INT_MAX === $this->measurementCount) {
            throw MetricAggregationException::overflow();
        }

        ++$this->measurementCount;
        $this->last = $value;
    }

    public function measurementCount(): int
    {
        return $this->measurementCount;
    }

    public function sum(): ?MetricNumber
    {
        return null;
    }

    public function last(): ?MetricNumber
    {
        return $this->last;
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
