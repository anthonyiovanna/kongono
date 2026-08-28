<?php

declare(strict_types=1);

namespace Kongono\Tracing\Tests\Unit;

use Kongono\Observability\AttributeSet;
use Kongono\Observability\ObservabilityContext;
use Kongono\Observability\ObservationRecorder;
use Kongono\Observability\ObservationStatus;
use Kongono\Observability\SignalIdentity;
use Kongono\Tracing\SpanId;
use Kongono\Tracing\TraceClock;
use Kongono\Tracing\TraceId;
use Kongono\Tracing\TraceIdentifierSource;
use Kongono\Tracing\TraceRuntime;
use Kongono\Tracing\TraceSampler;
use Kongono\Tracing\TraceSamplingDecision;
use Kongono\Tracing\TraceSamplingRequest;
use PHPUnit\Framework\TestCase;

final class TraceSamplingTest extends TestCase
{
    public function testSamplerRunsOnlyAtRootAndDropConstructsNoRecording(): void
    {
        $clock = $this->createMock(TraceClock::class);
        $clock->expects(self::exactly(4))->method('nowNanoseconds')->willReturnOnConsecutiveCalls(1, 2, 3, 4);
        $identifiers = $this->createMock(TraceIdentifierSource::class);
        $identifiers->expects(self::once())->method('nextTraceId')->willReturn(new TraceId(str_repeat('1', 32)));
        $identifiers->expects(self::exactly(2))->method('nextSpanId')->willReturnOnConsecutiveCalls(new SpanId(str_repeat('2', 16)), new SpanId(str_repeat('3', 16)));
        $sampler = $this->createMock(TraceSampler::class);
        $sampler->expects(self::once())->method('decide')->with(self::isInstanceOf(TraceSamplingRequest::class))->willReturn(TraceSamplingDecision::Drop);
        $recorder = $this->createMock(ObservationRecorder::class);
        $recorder->expects(self::never())->method('record');

        $operation = (new TraceRuntime($clock, $identifiers, $sampler, $recorder))->begin(
            ObservabilityContext::uncorrelated(),
            new SignalIdentity('trace.drop', 1),
            new AttributeSet([]),
        );
        $child = $operation->beginSpan(new SignalIdentity('trace.drop.child', 1), new AttributeSet([]));
        self::assertSame(TraceSamplingDecision::Drop, $operation->propagation()->samplingDecision());
        self::assertSame(TraceSamplingDecision::Drop, $child->propagation()->samplingDecision());
        $child->complete(ObservationStatus::Ok);
        $operation->complete(ObservationStatus::Ok);
    }
}
