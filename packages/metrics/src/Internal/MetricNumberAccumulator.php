<?php

declare(strict_types=1);

namespace Kongono\Metrics\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Metrics\Exception\MetricAggregationException;
use Kongono\Metrics\MetricNumber;

/** Computes exact finite native sums before committing aggregate mutation. */
#[Api(ApiStatus::Internal)]
final class MetricNumberAccumulator
{
    private int|float $value = 0;

    public function next(MetricNumber $addition): int|float
    {
        $right = $addition->value();
        if (is_int($this->value) && is_int($right)) {
            if (($right > 0 && $this->value > PHP_INT_MAX - $right) || ($right < 0 && $this->value < PHP_INT_MIN - $right)) {
                throw MetricAggregationException::overflow();
            }

            return $this->value + $right;
        }

        $sum = (float) $this->value + (float) $right;
        if (!is_finite($sum)) {
            throw MetricAggregationException::overflow();
        }

        return $sum;
    }

    public function commit(int|float $value): void
    {
        $this->value = $value;
    }

    public function metricNumber(): MetricNumber
    {
        return new MetricNumber($this->value);
    }
}
