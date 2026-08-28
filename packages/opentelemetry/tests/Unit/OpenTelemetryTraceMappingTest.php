<?php

declare(strict_types=1);

namespace Kongono\OpenTelemetry\Tests\Unit;

use Google\Protobuf\RepeatedField;
use Kongono\Observability\AttributeKey;
use Kongono\Observability\AttributeSet;
use Kongono\Observability\AttributeValue;
use Kongono\Observability\CorrelationId;
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
use Opentelemetry\Proto\Collector\Trace\V1\ExportTraceServiceRequest;
use Opentelemetry\Proto\Common\V1\AnyValue;
use Opentelemetry\Proto\Common\V1\InstrumentationScope;
use Opentelemetry\Proto\Common\V1\KeyValue;
use Opentelemetry\Proto\Trace\V1\ResourceSpans;
use Opentelemetry\Proto\Trace\V1\ScopeSpans;
use Opentelemetry\Proto\Trace\V1\Span;
use Opentelemetry\Proto\Trace\V1\Status;
use Opentelemetry\Proto\Trace\V1\Status\StatusCode;
use PHPUnit\Framework\TestCase;

final class OpenTelemetryTraceMappingTest extends TestCase
{
    public function testExactTraceRoundTripsWithoutInventedFields(): void
    {
        $transport = $this->transport();
        $bridge = $this->bridge($transport);
        $bridge->record(new Observation(
            ObservabilityContext::correlated(new CorrelationId('corr-1')),
            new SignalIdentity('orders.create', 7),
            ObservationKind::Trace,
            ObservationStatus::Error,
            new AttributeSet([
                $this->text('app.region', 'east'),
                $this->text('trace.id', '11111111111111111111111111111111'),
                $this->text('trace.span.id', '2222222222222222'),
                $this->text('trace.parent_span.id', '3333333333333333'),
                $this->attribute('trace.sampled', AttributeValue::boolean(true)),
                $this->attribute('trace.start_ns', AttributeValue::integer(110)),
                $this->attribute('trace.end_ns', AttributeValue::integer(125)),
                $this->attribute('trace.duration_ns', AttributeValue::integer(15)),
            ]),
        ));
        $bridge->flush();

        self::assertCount(1, $transport->payloads);
        $request = new ExportTraceServiceRequest();
        $request->mergeFromString($transport->payloads[0]);
        $resourceSpans = $this->firstMessage($request->getResourceSpans(), ResourceSpans::class);
        $scopeSpans = $this->firstMessage($resourceSpans->getScopeSpans(), ScopeSpans::class);
        $span = $this->firstMessage($scopeSpans->getSpans(), Span::class);
        $instrumentationScope = $scopeSpans->getScope();
        self::assertInstanceOf(InstrumentationScope::class, $instrumentationScope);
        self::assertSame('kongono.tracing', $instrumentationScope->getName());
        self::assertSame('10F-profile-1', $instrumentationScope->getVersion());
        self::assertSame('orders.create', $span->getName());
        self::assertSame(str_repeat("\x11", 16), $span->getTraceId());
        self::assertSame(str_repeat("\x22", 8), $span->getSpanId());
        self::assertSame(str_repeat("\x33", 8), $span->getParentSpanId());
        self::assertSame(1_010, $span->getStartTimeUnixNano());
        self::assertSame(1_025, $span->getEndTimeUnixNano());
        $status = $span->getStatus();
        self::assertInstanceOf(Status::class, $status);
        self::assertSame(StatusCode::STATUS_CODE_ERROR, $status->getCode());
        $events = $span->getEvents();
        $links = $span->getLinks();
        $attributeField = $span->getAttributes();
        self::assertInstanceOf(RepeatedField::class, $events);
        self::assertInstanceOf(RepeatedField::class, $links);
        self::assertInstanceOf(RepeatedField::class, $attributeField);
        self::assertCount(0, $events);
        self::assertCount(0, $links);
        $attributes = $this->attributes($attributeField);
        self::assertSame('corr-1', $attributes['kongono.correlation.id']->getStringValue());
        self::assertArrayNotHasKey('trace.id', $attributes);
    }

    private function bridge(OtlpHttpTransport $transport): OpenTelemetryBridge
    {
        return new OpenTelemetryBridge(
            new OpenTelemetryResource('orders'),
            new OpenTelemetryBatchPolicy(8, 4, 100),
            new OpenTelemetryTimeAnchor(100, 1_000),
            new class implements OpenTelemetryClock {
                public function epochNanoseconds(): int
                {
                    return 2_000;
                }
            },
            $transport,
        );
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
        return $this->attribute($key, AttributeValue::text($value));
    }
    private function attribute(string $key, AttributeValue $value): SignalAttribute
    {
        return new SignalAttribute(new AttributeKey($key), $value);
    }

    /**
     * @param iterable<KeyValue> $attributes
     * @return array<string, AnyValue>
     */
    private function attributes(iterable $attributes): array
    {
        $values = [];
        foreach ($attributes as $attribute) {
            $value = $attribute->getValue();
            self::assertInstanceOf(AnyValue::class, $value);
            $values[$attribute->getKey()] = $value;
        }
        return $values;
    }

    /**
     * @template T of object
     * @param class-string<T> $type
     * @return T
     */
    private function firstMessage(mixed $field, string $type): object
    {
        self::assertInstanceOf(RepeatedField::class, $field);
        foreach ($field as $message) {
            self::assertInstanceOf($type, $message);
            return $message;
        }
        self::fail('Expected one protobuf message.');
    }
}
