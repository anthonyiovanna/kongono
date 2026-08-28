<?php

declare(strict_types=1);

namespace Kongono\ObservabilityApplication;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Metrics\MetricPlan;
use Kongono\Observability\AttributeSet;
use Kongono\Observability\ObservabilityContext;
use Kongono\Observability\SignalIdentity;
use Kongono\Tracing\TracePropagation;

/** Carries the exact immutable inputs for one application observability operation. */
#[Api(ApiStatus::Experimental)]
final readonly class ApplicationObservabilityPlan
{
    public function __construct(
        private ObservabilityContext $context,
        private SignalIdentity $rootTraceIdentity,
        private AttributeSet $rootTraceAttributes,
        private MetricPlan $metricPlan,
        private ?TracePropagation $tracePropagation = null,
    ) {}

    public function context(): ObservabilityContext
    {
        return $this->context;
    }

    public function rootTraceIdentity(): SignalIdentity
    {
        return $this->rootTraceIdentity;
    }

    public function rootTraceAttributes(): AttributeSet
    {
        return $this->rootTraceAttributes;
    }

    public function metricPlan(): MetricPlan
    {
        return $this->metricPlan;
    }

    public function tracePropagation(): ?TracePropagation
    {
        return $this->tracePropagation;
    }
}
