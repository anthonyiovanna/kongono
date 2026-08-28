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
use Opentelemetry\Proto\Common\V1\AnyValue;
use Opentelemetry\Proto\Common\V1\KeyValue;
use Opentelemetry\Proto\Metrics\V1\ResourceMetrics;
use Opentelemetry\Proto\Resource\V1\Resource;
use OpenTelemetry\SemConv\Version;
use PHPUnit\Framework\TestCase;

final class OpenTelemetryProfileTest extends TestCase
{
    public function testExactProfileArgumentsResourceAndSchemaRoundTrip(): void
    {
        $transport = $this->transport();
        $bridge = new OpenTelemetryBridge(
            new OpenTelemetryResource('checkout', '1.2.3', 'test'),
            new OpenTelemetryBatchPolicy(4, 4, 321),
            new OpenTelemetryTimeAnchor(0, 1),
            new class implements OpenTelemetryClock {
                public function epochNanoseconds(): int
                {
                    return 777;
                }
            },
            $transport,
        );
        $bridge->record($this->gauge());
        self::assertSame([], $transport->calls);
        $bridge->flush();
        $call = $this->firstCall($transport->calls);
        self::assertSame(['Metrics', '/v1/metrics', 'application/x-protobuf', 321], [$call[0]->name, $call[1], $call[2], $call[4]]);
        $request = new ExportMetricsServiceRequest();
        $request->mergeFromString($call[3]);
        $resourceMetrics = $this->firstMessage($request->getResourceMetrics(), ResourceMetrics::class);
        self::assertSame(Version::VERSION_1_38_0->url(), $resourceMetrics->getSchemaUrl());
        $attributes = [];
        $resource = $resourceMetrics->getResource();
        self::assertInstanceOf(Resource::class, $resource);
        foreach ($this->messages($resource->getAttributes(), KeyValue::class) as $attribute) {
            $value = $attribute->getValue();
            self::assertInstanceOf(AnyValue::class, $value);
            $attributes[$attribute->getKey()] = $value->getStringValue();
        }
        self::assertSame(['deployment.environment.name' => 'test', 'service.name' => 'checkout', 'service.version' => '1.2.3', 'telemetry.sdk.language' => 'php', 'telemetry.sdk.name' => 'kongono'], $attributes);
    }

    private function gauge(): Observation
    {
        return new Observation(ObservabilityContext::uncorrelated(), new SignalIdentity('queue.depth', 1), ObservationKind::Metric, ObservationStatus::Unset, new AttributeSet([
            $this->text('metric.instrument', 'gauge'),
            $this->text('metric.unit', 'items'),
            new SignalAttribute(new AttributeKey('metric.measurement_count'), AttributeValue::integer(1)),
            new SignalAttribute(new AttributeKey('metric.last'), AttributeValue::integer(3)),
        ]));
    }

    /** @return OtlpHttpTransport&object{calls: list<array{OtlpSignal, string, string, string, int}>} */
    private function transport(): object
    {
        return new class implements OtlpHttpTransport {
            /** @var list<array{OtlpSignal, string, string, string, int}> */ public array $calls = [];
            public function send(OtlpSignal $signal, string $endpointSuffix, string $contentType, string $payload, int $timeoutMilliseconds): void
            {
                $this->calls[] = [$signal, $endpointSuffix, $contentType, $payload, $timeoutMilliseconds];
            }
            public function flush(int $timeoutMilliseconds): void {}
            public function close(int $timeoutMilliseconds): void {}
        };
    }

    private function text(string $key, string $value): SignalAttribute
    {
        return new SignalAttribute(new AttributeKey($key), AttributeValue::text($value));
    }

    /** @return array{OtlpSignal, string, string, string, int} */
    private function firstCall(mixed $calls): array
    {
        self::assertIsArray($calls);
        foreach ($calls as $call) {
            self::assertIsArray($call);
            self::assertCount(5, $call);
            self::assertInstanceOf(OtlpSignal::class, $call[0]);
            self::assertIsString($call[1]);
            self::assertIsString($call[2]);
            self::assertIsString($call[3]);
            self::assertIsInt($call[4]);
            return [$call[0], $call[1], $call[2], $call[3], $call[4]];
        }
        self::fail('Expected one transport call.');
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
