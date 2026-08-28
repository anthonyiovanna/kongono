<?php

declare(strict_types=1);

namespace Kongono\Metrics;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Metrics\Exception\InvalidMetricSchemaException;
use Kongono\Observability\SignalIdentity;

/** Defines one exact immutable metric instrument and its predeclared schema. */
#[Api(ApiStatus::Experimental)]
final readonly class MetricInstrument
{
    public function __construct(
        private SignalIdentity $identity,
        private MetricInstrumentKind $kind,
        private MetricUnit $unit,
        private MetricDimensionSchema $dimensions,
        private ?HistogramBoundaries $boundaries = null,
    ) {
        if (($kind === MetricInstrumentKind::Histogram) !== (null !== $boundaries)) {
            throw InvalidMetricSchemaException::instrument();
        }
    }

    public function identity(): SignalIdentity
    {
        return $this->identity;
    }

    public function kind(): MetricInstrumentKind
    {
        return $this->kind;
    }

    public function unit(): MetricUnit
    {
        return $this->unit;
    }

    public function dimensions(): MetricDimensionSchema
    {
        return $this->dimensions;
    }

    public function boundaries(): ?HistogramBoundaries
    {
        return $this->boundaries;
    }
}
