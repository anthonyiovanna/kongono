<?php

declare(strict_types=1);

namespace Kongono\Metrics\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Metrics\Exception\MetricAggregationException;
use Kongono\Metrics\MetricInstrument;
use Kongono\Metrics\MetricInstrumentKind;

/** Creates only the aggregate implementation fixed by an exact instrument kind. */
#[Api(ApiStatus::Internal)]
final class MetricAggregateFactory
{
    private function __construct() {}

    public static function create(MetricInstrument $instrument): MetricAggregate
    {
        return match ($instrument->kind()) {
            MetricInstrumentKind::Counter, MetricInstrumentKind::UpDownCounter => new SumMetricAggregate(),
            MetricInstrumentKind::Gauge => new GaugeMetricAggregate(),
            MetricInstrumentKind::Histogram => new HistogramMetricAggregate(
                $instrument->boundaries() ?? throw MetricAggregationException::overflow(),
            ),
        };
    }
}
