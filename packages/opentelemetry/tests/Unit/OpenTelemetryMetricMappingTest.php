<?php

declare(strict_types=1);

namespace Kongono\OpenTelemetry\Tests\Unit;

use Google\Protobuf\RepeatedField;
use Kongono\Observability\AttributeKey;
use Kongono\Observability\AttributeSet;
use Kongono\Observability\AttributeValue;
use Kongono\Observability\ObservabilityContext;
use Kongono\Observability\Observation;
use Kongono\Observability\ObservationKind;
use Kongono\Observability\ObservationStatus;
use Kongono\Observability\SignalAttribute;
use Kongono\Observability\SignalIdentity;
use Kongono\OpenTelemetry\OpenTelemetryBatchPolicy;
use Kongono\OpenTelemetry\OpenTelemetryBridge;
use Kongono\OpenTelemetry\OpenTelemetryClock;
use Kongono\OpenTelemetry\OpenTelemetryResource;
use Kongono\OpenTelemetry\OpenTelemetryTimeAnchor;
use Kongono\OpenTelemetry\OtlpHttpTransport;
use Kongono\OpenTelemetry\OtlpSignal;
use Opentelemetry\Proto\Collector\Metrics\V1\ExportMetricsServiceRequest;
use Opentelemetry\Proto\Common\V1\InstrumentationScope;
use Opentelemetry\Proto\Metrics\V1\AggregationTemporality;
use Opentelemetry\Proto\Metrics\V1\Gauge;
use Opentelemetry\Proto\Metrics\V1\Metric;
use Opentelemetry\Proto\Metrics\V1\NumberDataPoint;
use Opentelemetry\Proto\Metrics\V1\ResourceMetrics;
use Opentelemetry\Proto\Metrics\V1\ScopeMetrics;
use Opentelemetry\Proto\Metrics\V1\Sum;
use PHPUnit\Framework\TestCase;

final class OpenTelemetryMetricMappingTest extends TestCase
{
    public function testAllAggregateMappingsRoundTripIncludingHistogramFamily(): void
    {
        $transport = $this->transport();
        $bridge = $this->bridge($transport);
        $bridge->record($this->metric('requests', 'counter', ['metric.sum' => 5]));
        $bridge->record($this->metric('balance', 'up_down_counter', ['metric.sum' => -2]));
        $bridge->record($this->metric('temperature', 'gauge', ['metric.last' => 2.5]));
        $bridge->record($this->metric('latency', 'histogram', [
            'metric.sum' => 13,
            'metric.min' => 1,
            'metric.max' => 9,
            'metric.bucket.00.count' => 1,
            'metric.bucket.01.count' => 1,
        ], 2));
        $bridge->flush();

        self::assertCount(1, $transport->payloads);
        $request = new ExportMetricsServiceRequest();
        $request->mergeFromString($transport->payloads[0]);
        $resource = $this->firstMessage($request->getResourceMetrics(), ResourceMetrics::class);
        $scope = $this->firstMessage($resource->getScopeMetrics(), ScopeMetrics::class);
        $instrumentationScope = $scope->getScope();
        self::assertInstanceOf(InstrumentationScope::class, $instrumentationScope);
        self::assertSame('kongono.metrics', $instrumentationScope->getName());
        $metrics = $this->messages($scope->getMetrics(), Metric::class);
        self::assertCount(9, $metrics);
        self::assertSame('requests', $metrics[0]->getName());
        $counter = $metrics[0]->getSum();
        $upDownCounter = $metrics[1]->getSum();
        self::assertInstanceOf(Sum::class, $counter);
        self::assertInstanceOf(Sum::class, $upDownCounter);
        self::assertSame(AggregationTemporality::AGGREGATION_TEMPORALITY_CUMULATIVE, $counter->getAggregationTemporality());
        self::assertTrue($counter->getIsMonotonic());
        self::assertFalse($upDownCounter->getIsMonotonic());
        self::assertSame('latency.count', $metrics[3]->getName());
        self::assertSame('latency.bucket.01.count', $metrics[8]->getName());
        $gauge = $metrics[3]->getGauge();
        self::assertInstanceOf(Gauge::class, $gauge);
        $point = $this->firstMessage($gauge->getDataPoints(), NumberDataPoint::class);
        self::assertSame(5_000, $point->getTimeUnixNano());
        self::assertSame(2, $point->getAsInt());
    }

    /** @param array<string, int|float> $members */
    private function metric(string $name, string $instrument, array $members, int $count = 1): Observation
    {
        $attributes = [
            $this->text('metric.instrument', $instrument),
            $this->text('metric.unit', 'items'),
            $this->number('metric.measurement_count', $count),
        ];
        foreach ($members as $key => $value) {
            $attributes[] = $this->number($key, $value);
        }
        return new Observation(ObservabilityContext::uncorrelated(), new SignalIdentity($name, 1), ObservationKind::Metric, ObservationStatus::Unset, new AttributeSet($attributes));
    }

    private function bridge(OtlpHttpTransport $transport): OpenTelemetryBridge
    {
        return new OpenTelemetryBridge(new OpenTelemetryResource('metrics'), new OpenTelemetryBatchPolicy(8, 8, 100), new OpenTelemetryTimeAnchor(0, 1), new class implements OpenTelemetryClock {
            public function epochNanoseconds(): int
            {
                return 5_000;
            }
        }, $transport);
    }

    /** @return OtlpHttpTransport&object{payloads: list<string>} */
    private function transport(): object
    {
        return new class implements OtlpHttpTransport {
            /** @var list<string> */ public array $payloads = [];
            public function send(OtlpSignal $signal, string $endpointSuffix, string $contentType, string $payload, int $timeoutMilliseconds): void
            {
                $this->payloads[] = $payload;
            }
            public function flush(int $timeoutMilliseconds): void {}
            public function close(int $timeoutMilliseconds): void {}
        };
    }

    private function text(string $key, string $value): SignalAttribute
    {
        return new SignalAttribute(new AttributeKey($key), AttributeValue::text($value));
    }
    private function number(string $key, int|float $value): SignalAttribute
    {
        return new SignalAttribute(new AttributeKey($key), is_int($value) ? AttributeValue::integer($value) : AttributeValue::floatingPoint($value));
    }

    /**
     * @template T of object
     * @param class-string<T> $type
     * @return T
     */
    private function firstMessage(mixed $field, string $type): object
    {
        $messages = $this->messages($field, $type);
        foreach ($messages as $message) {
            return $message;
        }
        self::fail('Expected one protobuf message.');
    }

    /**
     * @template T of object
     * @param class-string<T> $type
     * @return list<T>
     */
    private function messages(mixed $field, string $type): array
    {
        self::assertInstanceOf(RepeatedField::class, $field);
        $messages = [];
        foreach ($field as $message) {
            self::assertInstanceOf($type, $message);
            $messages[] = $message;
        }
        return $messages;
    }
}
