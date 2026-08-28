<?php

declare(strict_types=1);

namespace Kongono\Metrics\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Observability\AttributeSet;

/** Retains one canonical bounded dimension assignment and its aggregate only. */
#[Api(ApiStatus::Internal)]
final readonly class MetricSeries
{
    public function __construct(private AttributeSet $dimensions, private MetricAggregate $aggregate) {}

    public function dimensions(): AttributeSet
    {
        return $this->dimensions;
    }

    public function aggregate(): MetricAggregate
    {
        return $this->aggregate;
    }
}
