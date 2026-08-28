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

final class TraceIsolationTest extends TestCase
{
    public function testOverlappingRetirementAndOperationLocalReferenceReleaseAreIsolated(): void
    {
        $identifiers = new class implements TraceIdentifierSource {
            public int $trace = 0;
            public int $span = 0;
            public function nextTraceId(): TraceId
            {
                return new TraceId(sprintf('%032x', ++$this->trace));
            }
            public function nextSpanId(): SpanId
            {
                return new SpanId(sprintf('%016x', ++$this->span));
            }
        };
        $clock = new class implements TraceClock {
            public int $calls = 0;
            public function nowNanoseconds(): int
            {
                return ++$this->calls;
            }
        };
        $sampler = new class implements TraceSampler {
            public function decide(\Kongono\Tracing\TraceSamplingRequest $request): TraceSamplingDecision
            {
                return TraceSamplingDecision::Drop;
            }
        };
        $recorder = $this->createMock(ObservationRecorder::class);
        $recorder->expects(self::never())->method('record');
        $runtime = new TraceRuntime($clock, $identifiers, $sampler, $recorder);

        $context = ObservabilityContext::uncorrelated();
        $identity = new SignalIdentity('trace.release', 1);
        $attributes = new AttributeSet([]);
        $contextReference = \WeakReference::create($context);
        $identityReference = \WeakReference::create($identity);
        $attributesReference = \WeakReference::create($attributes);
        $left = $runtime->begin($context, $identity, $attributes);
        $right = $runtime->begin(ObservabilityContext::uncorrelated(), new SignalIdentity('trace.right', 1), new AttributeSet([]));
        $left->beginSpan(new SignalIdentity('trace.left.child', 1), new AttributeSet([]));
        $right->beginSpan(new SignalIdentity('trace.right.child', 1), new AttributeSet([]));
        self::assertSame(1, $left->activeSpanCount());
        self::assertSame(1, $right->activeSpanCount());
        $calls = $clock->calls;
        unset($context, $identity, $attributes);
        $left->retire();
        gc_collect_cycles();

        self::assertSame(TraceLifecycleState::Retired, $left->state());
        self::assertSame(1, $right->activeSpanCount());
        self::assertSame($calls, $clock->calls);
        self::assertNull($contextReference->get());
        self::assertNull($identityReference->get());
        self::assertNull($attributesReference->get());
        $right->retire();
        $right->retire();
        self::assertSame($calls, $clock->calls);
    }

    public function testRecorderReentrancyCreatesFreshIsolatedOperation(): void
    {
        $identifiers = new class implements TraceIdentifierSource {
            public int $trace = 0;
            public int $span = 0;
            public function nextTraceId(): TraceId
            {
                return new TraceId(sprintf('%032x', ++$this->trace));
            }
            public function nextSpanId(): SpanId
            {
                return new SpanId(sprintf('%016x', ++$this->span));
            }
        };
        $clock = new class implements TraceClock {
            public int $tick = 0;
            public function nowNanoseconds(): int
            {
                return ++$this->tick;
            }
        };
        $sampler = new class implements TraceSampler {
            public function decide(\Kongono\Tracing\TraceSamplingRequest $request): TraceSamplingDecision
            {
                return TraceSamplingDecision::Record;
            }
        };
        $recorder = new class implements ObservationRecorder {
            public ?TraceRuntime $runtime = null;
            public int $calls = 0;
            public function record(Observation $observation): void
            {
                ++$this->calls;
                if (1 === $this->calls) {
                    $runtime = $this->runtime ?? throw new \LogicException('Runtime not assigned.');
                    $nested = $runtime->begin(ObservabilityContext::uncorrelated(), new SignalIdentity('trace.reentrant', 1), new AttributeSet([]));
                    $nested->complete(ObservationStatus::Ok);
                }
            }
        };
        $runtime = new TraceRuntime($clock, $identifiers, $sampler, $recorder);
        $recorder->runtime = $runtime;
        $outer = $runtime->begin(ObservabilityContext::uncorrelated(), new SignalIdentity('trace.outer', 1), new AttributeSet([]));
        $outer->complete(ObservationStatus::Ok);

        self::assertSame(TraceLifecycleState::Completed, $outer->state());
        self::assertSame(2, $recorder->calls);
        self::assertSame(2, $identifiers->trace);
        self::assertSame(2, $identifiers->span);
    }
}
