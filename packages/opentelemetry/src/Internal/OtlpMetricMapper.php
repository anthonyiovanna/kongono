<?php

declare(strict_types=1);

namespace Kongono\OpenTelemetry\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Observability\AttributeValue;
use Kongono\Observability\AttributeValueKind;
use Kongono\Observability\ObservationKind;
use Kongono\OpenTelemetry\Exception\OpenTelemetryMappingException;
use Opentelemetry\Proto\Metrics\V1\AggregationTemporality;
use Opentelemetry\Proto\Metrics\V1\Gauge;
use Opentelemetry\Proto\Metrics\V1\Metric;
use Opentelemetry\Proto\Metrics\V1\NumberDataPoint;
use Opentelemetry\Proto\Metrics\V1\Sum;

/** Maps exact aggregate observations without fabricating histogram boundaries. */
#[Api(ApiStatus::Internal)]
final class OtlpMetricMapper
{
    private function __construct() {}

    /**
     * @param list<QueuedObservation> $records
     * @return list<Metric>
     */
    public static function map(array $records): array
    {
        $metrics = [];
        foreach ($records as $record) {
            $observation = $record->observation();
            if ($observation->kind() !== ObservationKind::Metric) {
                throw OpenTelemetryMappingException::invalid(signalKind: $observation->kind()->value);
            }
            $reserved = self::reserved($observation);
            $instrument = self::text($reserved, 'metric.instrument');
            $unit = self::text($reserved, 'metric.unit');
            $count = self::integer($reserved, 'metric.measurement_count');
            if ($count < 1) {
                throw OpenTelemetryMappingException::invalid(signalKind: 'metric');
            }
            $additional = [];
            if ('histogram' === $instrument) {
                $metrics = [...$metrics, ...self::histogramFamily($record, $reserved, $unit, $count)];
                continue;
            }

            $required = match ($instrument) {
                'counter', 'up_down_counter' => ['metric.instrument', 'metric.unit', 'metric.measurement_count', 'metric.sum'],
                'gauge' => ['metric.instrument', 'metric.unit', 'metric.measurement_count', 'metric.last'],
                default => throw OpenTelemetryMappingException::invalid(signalKind: 'metric'),
            };
            if ([] !== array_diff(array_keys($reserved), $required) || [] !== array_diff($required, array_keys($reserved))) {
                throw OpenTelemetryMappingException::invalid(signalKind: 'metric');
            }
            $number = self::number($reserved, 'gauge' === $instrument ? 'metric.last' : 'metric.sum');
            $point = self::point($record, $number, $additional);
            $metric = (new Metric())->setName($observation->identity()->name())->setUnit($unit);
            if ('gauge' === $instrument) {
                $metric->setGauge((new Gauge())->setDataPoints([$point]));
            } else {
                $metric->setSum(
                    (new Sum())
                        ->setDataPoints([$point])
                        ->setAggregationTemporality(AggregationTemporality::AGGREGATION_TEMPORALITY_CUMULATIVE)
                        ->setIsMonotonic('counter' === $instrument),
                );
            }
            $metrics[] = $metric;
        }

        return $metrics;
    }

    /**
     * @param array<string, AttributeValue> $reserved
     * @return list<Metric>
     */
    private static function histogramFamily(QueuedObservation $record, array $reserved, string $unit, int $count): array
    {
        $required = ['metric.instrument', 'metric.unit', 'metric.measurement_count', 'metric.sum', 'metric.min', 'metric.max'];
        $buckets = [];
        foreach ($reserved as $key => $value) {
            if (1 === preg_match('/\Ametric\.bucket\.(\d{2})\.count\z/D', $key, $matches)) {
                $index = (int) $matches[1];
                if ($index !== count($buckets)) {
                    throw OpenTelemetryMappingException::invalid(signalKind: 'metric');
                }
                $bucketCount = self::integer($reserved, $key);
                $buckets[] = [$index, $bucketCount];
                $required[] = $key;
            }
        }
        if ([] === $buckets || array_sum(array_column($buckets, 1)) !== $count || [] !== array_diff(array_keys($reserved), $required) || [] !== array_diff($required, array_keys($reserved))) {
            throw OpenTelemetryMappingException::invalid(signalKind: 'metric');
        }
        $observation = $record->observation();
        $base = $observation->identity()->name();
        $additional = ['kongono.metric.mapping' => 'histogram_aggregate_v1'];
        $members = [
            [$base . '.count', $count],
            [$base . '.sum', self::number($reserved, 'metric.sum')],
            [$base . '.min', self::number($reserved, 'metric.min')],
            [$base . '.max', self::number($reserved, 'metric.max')],
        ];
        foreach ($buckets as [$index, $bucketCount]) {
            $members[] = [sprintf('%s.bucket.%02d.count', $base, $index), $bucketCount];
        }
        $metrics = [];
        foreach ($members as [$name, $number]) {
            $metrics[] = (new Metric())
                ->setName($name)
                ->setUnit($unit)
                ->setGauge((new Gauge())->setDataPoints([self::point($record, $number, $additional)]));
        }

        return $metrics;
    }

    /**
     * @param array<string, bool|int|float|string> $additional
     */
    private static function point(QueuedObservation $record, int|float $number, array $additional): NumberDataPoint
    {
        $point = (new NumberDataPoint())
            ->setAttributes(OtlpAttributeMapper::observation($record->observation(), ['metric.'], $additional))
            ->setTimeUnixNano($record->epochNanoseconds());
        if (is_int($number)) {
            $point->setAsInt($number);
        } else {
            $point->setAsDouble($number);
        }

        return $point;
    }

    /** @return array<string, AttributeValue> */
    private static function reserved(\Kongono\Observability\Observation $observation): array
    {
        $values = [];
        foreach ($observation->attributes()->attributes() as $attribute) {
            if (str_starts_with($attribute->key()->value(), 'metric.')) {
                $values[$attribute->key()->value()] = $attribute->value();
            }
        }

        return $values;
    }

    /** @param array<string, AttributeValue> $values */
    private static function text(array $values, string $key): string
    {
        $value = $values[$key] ?? null;
        if (null === $value || $value->kind() !== AttributeValueKind::Text || !is_string($value->value())) {
            throw OpenTelemetryMappingException::invalid(signalKind: 'metric');
        }

        return $value->value();
    }

    /** @param array<string, AttributeValue> $values */
    private static function integer(array $values, string $key): int
    {
        $value = $values[$key] ?? null;
        if (null === $value || $value->kind() !== AttributeValueKind::Integer || !is_int($value->value()) || $value->value() < 0) {
            throw OpenTelemetryMappingException::invalid(signalKind: 'metric');
        }

        return $value->value();
    }

    /** @param array<string, AttributeValue> $values */
    private static function number(array $values, string $key): int|float
    {
        $value = $values[$key] ?? null;
        if (null === $value || !in_array($value->kind(), [AttributeValueKind::Integer, AttributeValueKind::FloatingPoint], true) || (!is_int($value->value()) && !is_float($value->value()))) {
            throw OpenTelemetryMappingException::invalid(signalKind: 'metric');
        }

        return $value->value();
    }
}
