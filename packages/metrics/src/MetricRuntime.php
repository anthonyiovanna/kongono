<?php

declare(strict_types=1);

namespace Kongono\Metrics;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Metrics\Internal\AggregatingMetricCollection;
use Kongono\Observability\ObservabilityContext;
use Kongono\Observability\ObservationRecorder;

/** Begins fresh isolated metric collections over one explicit recorder capability. */
#[Api(ApiStatus::Experimental)]
final readonly class MetricRuntime
{
    public function __construct(private ObservationRecorder $recorder) {}

    public function begin(ObservabilityContext $context, MetricPlan $plan): MetricCollection
    {
        return new AggregatingMetricCollection($context, $plan, $this->recorder);
    }
}
