<?php

declare(strict_types=1);

namespace Kongono\Metrics;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Observability\AttributeSet;

/** Carries one exact finite value with one bounded dimension assignment. */
#[Api(ApiStatus::Experimental)]
final readonly class MetricMeasurement
{
    public function __construct(private MetricNumber $value, private AttributeSet $dimensions) {}

    public function value(): MetricNumber
    {
        return $this->value;
    }

    public function dimensions(): AttributeSet
    {
        return $this->dimensions;
    }
}
