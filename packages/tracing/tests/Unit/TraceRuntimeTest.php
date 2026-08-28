<?php

declare(strict_types=1);

namespace Kongono\Tracing\Tests\Unit;

use Kongono\Observability\AttributeSet;
use Kongono\Observability\ObservabilityContext;
use Kongono\Observability\ObservationRecorder;
use Kongono\Observability\SignalIdentity;
use Kongono\Tracing\SpanId;
use Kongono\Tracing\TraceClock;
use Kongono\Tracing\TraceId;
use Kongono\Tracing\TraceIdentifierSource;
use Kongono\Tracing\TracePropagation;
use Kongono\Tracing\TraceRuntime;
use Kongono\Tracing\TraceSampler;
use Kongono\Tracing\TraceSamplingDecision;
use Kongono\Tracing\TraceSamplingRequest;
use PHPUnit\Framework\TestCase;

final class TraceRuntimeTest extends TestCase
{
    public function testNewRootUsesExactCollaboratorOrderAndCounts(): void
    {
        $calls = [];
        $traceId = new TraceId(str_repeat('1', 32));
        $spanId = new SpanId(str_repeat('2', 16));
        $identifiers = $this->createMock(TraceIdentifierSource::class);
        $identifiers->expects(self::once())->method('nextTraceId')->willReturnCallback(function () use (&$calls, $traceId): TraceId {
            $calls[] = 'trace_id';

            return $traceId;
        });
        $identifiers->expects(self::once())->method('nextSpanId')->willReturnCallback(function () use (&$calls, $spanId): SpanId {
            $calls[] = 'span_id';

            return $spanId;
        });
        $sampler = $this->createMock(TraceSampler::class);
        $sampler->expects(self::once())->method('decide')->willReturnCallback(function (TraceSamplingRequest $request) use (&$calls, $traceId): TraceSamplingDecision {
            $calls[] = 'sample';
            self::assertSame($traceId, $request->traceId());
            self::assertNull($request->parent());

            return TraceSamplingDecision::Record;
        });
        $clock = $this->createMock(TraceClock::class);
        $clock->expects(self::once())->method('nowNanoseconds')->willReturnCallback(function () use (&$calls): int {
            $calls[] = 'clock';

            return 10;
        });
        $recorder = self::createStub(ObservationRecorder::class);

        $operation = (new TraceRuntime($clock, $identifiers, $sampler, $recorder))->begin(
            ObservabilityContext::uncorrelated(),
            new SignalIdentity('trace.root', 1),
            new AttributeSet([]),
        );

        self::assertSame(['trace_id', 'span_id', 'sample', 'clock'], $calls);
        self::assertSame($traceId, $operation->traceId());
        self::assertSame($spanId, $operation->spanId());
        self::assertNull($operation->parentSpanId());
        $operation->retire();
    }

    public function testInboundParentReusesTraceAndRejectsEqualRootSpan(): void
    {
        $traceId = new TraceId(str_repeat('3', 32));
        $parentSpan = new SpanId(str_repeat('4', 16));
        $parent = new TracePropagation($traceId, $parentSpan, TraceSamplingDecision::Drop);
        $identifiers = $this->createMock(TraceIdentifierSource::class);
        $identifiers->expects(self::never())->method('nextTraceId');
        $identifiers->expects(self::once())->method('nextSpanId')->willReturn(new SpanId(str_repeat('5', 16)));
        $sampler = $this->createMock(TraceSampler::class);
        $sampler->expects(self::once())->method('decide')->with(self::callback(static fn(TraceSamplingRequest $request): bool => $request->parent() === $parent))->willReturn(TraceSamplingDecision::Drop);
        $clock = self::createStub(TraceClock::class);
        $clock->method('nowNanoseconds')->willReturn(1);
        $operation = (new TraceRuntime($clock, $identifiers, $sampler, self::createStub(ObservationRecorder::class)))->begin(
            ObservabilityContext::uncorrelated(),
            new SignalIdentity('trace.inbound', 1),
            new AttributeSet([]),
            $parent,
        );

        self::assertSame($traceId, $operation->traceId());
        self::assertSame($parentSpan, $operation->parentSpanId());
        $operation->retire();
    }
}
