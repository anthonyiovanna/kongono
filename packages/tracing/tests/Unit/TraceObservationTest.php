<?php

declare(strict_types=1);

namespace Kongono\Tracing\Tests\Unit;

use Kongono\Observability\AttributeKey;
use Kongono\Observability\AttributeSet;
use Kongono\Observability\AttributeValue;
use Kongono\Observability\AttributeValueKind;
use Kongono\Observability\ObservabilityContext;
use Kongono\Observability\Observation;
use Kongono\Observability\ObservationKind;
use Kongono\Observability\ObservationRecorder;
use Kongono\Observability\ObservationStatus;
use Kongono\Observability\SignalAttribute;
use Kongono\Observability\SignalIdentity;
use Kongono\Tracing\SpanId;
use Kongono\Tracing\TraceClock;
use Kongono\Tracing\TraceId;
use Kongono\Tracing\TraceIdentifierSource;
use Kongono\Tracing\TracePropagation;
use Kongono\Tracing\TraceRuntime;
use Kongono\Tracing\TraceSampler;
use Kongono\Tracing\TraceSamplingDecision;
use PHPUnit\Framework\TestCase;

final class TraceObservationTest extends TestCase
{
    public function testInboundRootMappingAndCallerObjectRetentionAreExact(): void
    {
        $context = ObservabilityContext::uncorrelated();
        $identity = new SignalIdentity('trace.inbound', 7);
        $callerAttribute = new SignalAttribute(new AttributeKey('app.exact'), AttributeValue::text('retained'));
        $callerAttributes = new AttributeSet([$callerAttribute]);
        $traceId = new TraceId(str_repeat('1', 32));
        $parentSpanId = new SpanId(str_repeat('2', 16));
        $rootSpanId = new SpanId(str_repeat('3', 16));
        $parent = new TracePropagation($traceId, $parentSpanId, TraceSamplingDecision::Drop);
        $clock = $this->createMock(TraceClock::class);
        $clock->expects(self::exactly(2))->method('nowNanoseconds')->willReturnOnConsecutiveCalls(100, 160);
        $identifiers = $this->createMock(TraceIdentifierSource::class);
        $identifiers->expects(self::never())->method('nextTraceId');
        $identifiers->expects(self::once())->method('nextSpanId')->willReturn($rootSpanId);
        $sampler = self::createStub(TraceSampler::class);
        $sampler->method('decide')->willReturn(TraceSamplingDecision::Record);
        $recorder = new class implements ObservationRecorder {
            public ?Observation $observation = null;
            public function record(Observation $observation): void
            {
                $this->observation = $observation;
            }
        };

        $operation = (new TraceRuntime($clock, $identifiers, $sampler, $recorder))->begin($context, $identity, $callerAttributes, $parent);
        $operation->complete(ObservationStatus::Error);
        $observation = $recorder->observation;
        self::assertNotNull($observation);
        self::assertSame($context, $observation->context());
        self::assertSame($identity, $observation->identity());
        self::assertSame(ObservationKind::Trace, $observation->kind());
        self::assertSame(ObservationStatus::Error, $observation->status());
        self::assertTrue(in_array($callerAttribute, $observation->attributes()->attributes(), true));
        $expected = [
            'trace.id' => [AttributeValueKind::Text, $traceId->value()],
            'trace.span.id' => [AttributeValueKind::Text, $rootSpanId->value()],
            'trace.parent_span.id' => [AttributeValueKind::Text, $parentSpanId->value()],
            'trace.sampled' => [AttributeValueKind::Boolean, true],
            'trace.start_ns' => [AttributeValueKind::Integer, 100],
            'trace.end_ns' => [AttributeValueKind::Integer, 160],
            'trace.duration_ns' => [AttributeValueKind::Integer, 60],
        ];
        foreach ($expected as $key => [$kind, $value]) {
            $actual = $observation->attributes()->find(new AttributeKey($key));
            self::assertNotNull($actual);
            self::assertSame($kind, $actual->kind());
            self::assertSame($value, $actual->value());
        }
        self::assertSame(8, $observation->attributes()->count());
    }

    public function testNewRootOmitsOnlyParentAttribute(): void
    {
        $clock = self::createStub(TraceClock::class);
        $clock->method('nowNanoseconds')->willReturnOnConsecutiveCalls(1, 1);
        $identifiers = self::createStub(TraceIdentifierSource::class);
        $identifiers->method('nextTraceId')->willReturn(new TraceId(str_repeat('4', 32)));
        $identifiers->method('nextSpanId')->willReturn(new SpanId(str_repeat('5', 16)));
        $sampler = self::createStub(TraceSampler::class);
        $sampler->method('decide')->willReturn(TraceSamplingDecision::Record);
        $recorder = new class implements ObservationRecorder {
            public ?Observation $observation = null;
            public function record(Observation $observation): void
            {
                $this->observation = $observation;
            }
        };
        $operation = (new TraceRuntime($clock, $identifiers, $sampler, $recorder))->begin(
            ObservabilityContext::uncorrelated(),
            new SignalIdentity('trace.new', 1),
            new AttributeSet([]),
        );
        $operation->complete(ObservationStatus::Ok);

        self::assertNotNull($recorder->observation);
        self::assertNull($recorder->observation->attributes()->find(new AttributeKey('trace.parent_span.id')));
        self::assertSame(6, $recorder->observation->attributes()->count());
    }
}
