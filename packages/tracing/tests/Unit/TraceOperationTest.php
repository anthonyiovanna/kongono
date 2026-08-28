<?php

declare(strict_types=1);

namespace Kongono\Tracing\Tests\Unit;

use Kongono\Observability\AttributeSet;
use Kongono\Observability\ObservabilityContext;
use Kongono\Observability\Observation;
use Kongono\Observability\ObservationRecorder;
use Kongono\Observability\ObservationStatus;
use Kongono\Observability\SignalIdentity;
use Kongono\Tracing\SpanId;
use Kongono\Tracing\TraceClock;
use Kongono\Tracing\TraceId;
use Kongono\Tracing\TraceIdentifierSource;
use Kongono\Tracing\TraceLifecycleState;
use Kongono\Tracing\TraceRuntime;
use Kongono\Tracing\TraceSampler;
use Kongono\Tracing\TraceSamplingDecision;
use PHPUnit\Framework\TestCase;

final class TraceOperationTest extends TestCase
{
    public function testRootAndChildLifecyclePropagationAndCompletionAreExplicit(): void
    {
        $ticks = [10, 20, 25, 30];
        $clock = new class ($ticks) implements TraceClock {
            /** @param list<int> $ticks */
            public function __construct(private array $ticks) {}
            public function nowNanoseconds(): int
            {
                return array_shift($this->ticks) ?? 0;
            }
        };
        $spanIds = [new SpanId(str_repeat('2', 16)), new SpanId(str_repeat('3', 16))];
        $identifiers = new class ($spanIds) implements TraceIdentifierSource {
            /** @param list<SpanId> $spanIds */
            public function __construct(private array $spanIds) {}
            public function nextTraceId(): TraceId
            {
                return new TraceId(str_repeat('1', 32));
            }
            public function nextSpanId(): SpanId
            {
                return array_shift($this->spanIds) ?? new SpanId(str_repeat('f', 16));
            }
        };
        $sampler = new class implements TraceSampler {
            public function decide(\Kongono\Tracing\TraceSamplingRequest $request): TraceSamplingDecision
            {
                return TraceSamplingDecision::Record;
            }
        };
        $recorder = new class implements ObservationRecorder {
            /** @var list<Observation> */
            public array $observations = [];
            public function record(Observation $observation): void
            {
                $this->observations[] = $observation;
            }
        };
        $operation = (new TraceRuntime($clock, $identifiers, $sampler, $recorder))->begin(
            ObservabilityContext::uncorrelated(),
            new SignalIdentity('trace.root', 1),
            new AttributeSet([]),
        );
        $child = $operation->beginSpan(new SignalIdentity('trace.child', 1), new AttributeSet([]));

        self::assertSame(1, $operation->activeSpanCount());
        self::assertSame($operation->spanId(), $child->parentSpanId());
        self::assertSame($operation->traceId(), $child->traceId());
        self::assertSame($operation->spanId(), $operation->propagation()->spanId());
        self::assertSame($child->spanId(), $child->propagation()->spanId());

        $child->complete(ObservationStatus::Ok);
        self::assertSame(TraceLifecycleState::Completed, $child->state());
        self::assertSame(0, $operation->activeSpanCount());
        $operation->complete(ObservationStatus::Error);
        self::assertSame(TraceLifecycleState::Completed, $operation->state());
        self::assertCount(2, $recorder->observations);

        $child->retire();
        $operation->retire();
        self::assertSame(TraceLifecycleState::Completed, $child->state());
        self::assertSame(TraceLifecycleState::Completed, $operation->state());
    }
}
