<?php

declare(strict_types=1);

namespace Kongono\Metrics\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Metrics\Exception\MetricAggregationException;
use Kongono\Metrics\MetricInstrument;
use Kongono\Metrics\MetricInstrumentKind;
use Kongono\Metrics\MetricNumber;
use Kongono\Observability\AttributeKey;
use Kongono\Observability\AttributeSet;
use Kongono\Observability\AttributeValue;
use Kongono\Observability\ObservabilityContext;
use Kongono\Observability\Observation;
use Kongono\Observability\ObservationKind;
use Kongono\Observability\ObservationStatus;
use Kongono\Observability\SignalAttribute;

/** Maps one populated bounded series to the exact neutral metric observation. */
#[Api(ApiStatus::Internal)]
final class MetricObservationFactory
{
    private function __construct() {}

    public static function create(ObservabilityContext $context, MetricInstrument $instrument, MetricSeries $series): Observation
    {
        $aggregate = $series->aggregate();
        $attributes = $series->dimensions()->attributes();
        $attributes[] = self::text('metric.instrument', $instrument->kind()->value);
        $attributes[] = self::text('metric.unit', $instrument->unit()->value());
        $attributes[] = self::integer('metric.measurement_count', $aggregate->measurementCount());

        if ($instrument->kind() === MetricInstrumentKind::Gauge) {
            $attributes[] = self::number('metric.last', $aggregate->last() ?? throw MetricAggregationException::overflow());
        } else {
            $attributes[] = self::number('metric.sum', $aggregate->sum() ?? throw MetricAggregationException::overflow());
        }

        if ($instrument->kind() === MetricInstrumentKind::Histogram) {
            $attributes[] = self::number('metric.min', $aggregate->minimum() ?? throw MetricAggregationException::overflow());
            $attributes[] = self::number('metric.max', $aggregate->maximum() ?? throw MetricAggregationException::overflow());
            foreach ($aggregate->bucketCounts() as $index => $count) {
                $attributes[] = self::integer(sprintf('metric.bucket.%02d.count', $index), $count);
            }
        }

        return new Observation(
            $context,
            $instrument->identity(),
            ObservationKind::Metric,
            ObservationStatus::Unset,
            new AttributeSet($attributes),
        );
    }

    private static function text(string $key, string $value): SignalAttribute
    {
        return new SignalAttribute(new AttributeKey($key), AttributeValue::text($value));
    }

    private static function integer(string $key, int $value): SignalAttribute
    {
        return new SignalAttribute(new AttributeKey($key), AttributeValue::integer($value));
    }

    private static function number(string $key, MetricNumber $number): SignalAttribute
    {
        $value = $number->value();

        return new SignalAttribute(new AttributeKey($key), is_int($value) ? AttributeValue::integer($value) : AttributeValue::floatingPoint($value));
    }
}
