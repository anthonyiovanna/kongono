<?php

declare(strict_types=1);

namespace Kongono\Tracing\Tests\Unit;

use Kongono\Observability\AttributeKey;
use Kongono\Observability\AttributeSet;
use Kongono\Observability\AttributeValue;
use Kongono\Observability\ObservabilityContext;
use Kongono\Observability\ObservationRecorder;
use Kongono\Observability\ObservationStatus;
use Kongono\Observability\SignalAttribute;
use Kongono\Observability\SignalIdentity;
use Kongono\Tracing\Exception\InvalidTracingValueException;
use Kongono\Tracing\Exception\TracingLifecycleException;
use Kongono\Tracing\SpanId;
use Kongono\Tracing\TraceClock;
use Kongono\Tracing\TraceId;
use Kongono\Tracing\TraceIdentifierSource;
use Kongono\Tracing\TracePropagation;
use Kongono\Tracing\TraceRuntime;
use Kongono\Tracing\TraceSampler;
use Kongono\Tracing\TraceSamplingDecision;
use PHPUnit\Framework\TestCase;

final class TraceLifecycleFailureTest extends TestCase
{
    public function testActiveChildStateUnsetStatusAndInvalidTimingFailVisibly(): void
    {
        $operation = $this->operation(
            [new SpanId(str_repeat('f', 16)), new SpanId(str_repeat('1', 16))],
            [10, 20, 30],
        );
        $child = $operation->beginSpan(new SignalIdentity('trace.child', 1), new AttributeSet([]));
        $this->expectFailure(TracingLifecycleException::class, static fn() => $operation->complete(ObservationStatus::Ok));
        $this->expectFailure(InvalidTracingValueException::class, static fn() => $child->complete(ObservationStatus::Unset));
        $child->retire();
        $this->expectFailure(InvalidTracingValueException::class, static fn() => $operation->complete(ObservationStatus::Unset));
        $operation->retire();

        $badEnd = $this->operation([new SpanId(str_repeat('e', 16))], [20, 19]);
        $this->expectFailure(InvalidTracingValueException::class, static fn() => $badEnd->complete(ObservationStatus::Ok));
        $badEnd->retire();
    }

    public function testForeignStaleAndDuplicateParentsAndIdentifiersAreRejected(): void
    {
        $left = $this->operation([new SpanId(str_repeat('a', 16)), new SpanId(str_repeat('b', 16))], [1, 2, 3]);
        $right = $this->operation([new SpanId(str_repeat('c', 16)), new SpanId(str_repeat('d', 16))], [1, 2, 3]);
        $foreign = $right->beginSpan(new SignalIdentity('trace.foreign', 1), new AttributeSet([]));
        $this->expectFailure(TracingLifecycleException::class, static fn() => $left->beginSpan(new SignalIdentity('trace.reject', 1), new AttributeSet([]), $foreign));
        $foreign->complete(ObservationStatus::Ok);
        $this->expectFailure(TracingLifecycleException::class, static fn() => $right->beginSpan(new SignalIdentity('trace.stale', 1), new AttributeSet([]), $foreign));
        $left->retire();
        $right->retire();

        $duplicate = $this->operation([new SpanId(str_repeat('9', 16)), new SpanId(str_repeat('9', 16))], [1, 2]);
        $this->expectFailure(InvalidTracingValueException::class, static fn() => $duplicate->beginSpan(new SignalIdentity('trace.duplicate', 1), new AttributeSet([])));
        $duplicate->retire();

        $parentSpan = new SpanId(str_repeat('7', 16));
        $parent = new TracePropagation(new TraceId(str_repeat('8', 32)), $parentSpan, TraceSamplingDecision::Drop);
        $runtime = $this->runtime([$parentSpan], [1]);
        $this->expectFailure(InvalidTracingValueException::class, static fn() => $runtime->begin(
            ObservabilityContext::uncorrelated(),
            new SignalIdentity('trace.inbound.duplicate', 1),
            new AttributeSet([]),
            $parent,
        ));
    }

    public function testAttributeAndActiveChildBoundsAreExact(): void
    {
        $maximum = [];
        for ($index = 0; $index < 25; ++$index) {
            $maximum[] = new SignalAttribute(new AttributeKey(sprintf('app.key.%02d', $index)), AttributeValue::integer($index));
        }
        $operation = $this->operation([new SpanId(str_repeat('f', 16))], [1]);
        $operation->retire();
        $bounded = $this->runtime([new SpanId(str_repeat('e', 16))], [1])->begin(
            ObservabilityContext::uncorrelated(),
            new SignalIdentity('trace.attributes', 1),
            new AttributeSet($maximum),
        );
        $bounded->retire();

        $tooMany = $maximum;
        $tooMany[] = new SignalAttribute(new AttributeKey('app.key.25'), AttributeValue::integer(25));
        $this->expectFailure(InvalidTracingValueException::class, fn() => $this->runtime([new SpanId(str_repeat('d', 16))], [1])->begin(
            ObservabilityContext::uncorrelated(),
            new SignalIdentity('trace.attributes.too_many', 1),
            new AttributeSet($tooMany),
        ));
        foreach (['trace.id', 'trace.span.id', 'trace.parent_span.id', 'trace.sampled', 'trace.start_ns', 'trace.end_ns', 'trace.duration_ns'] as $reserved) {
            $this->expectFailure(InvalidTracingValueException::class, fn() => $this->runtime([new SpanId(str_repeat('c', 16))], [1])->begin(
                ObservabilityContext::uncorrelated(),
                new SignalIdentity('trace.attributes.reserved', 1),
                new AttributeSet([new SignalAttribute(new AttributeKey($reserved), AttributeValue::text('private-value'))]),
            ));
        }

        $spanIds = [new SpanId(str_repeat('f', 16))];
        for ($index = 1; $index <= 64; ++$index) {
            $spanIds[] = new SpanId(sprintf('%016x', $index));
        }
        $capacity = $this->operation($spanIds, array_fill(0, 65, 1));
        for ($index = 0; $index < 64; ++$index) {
            $capacity->beginSpan(new SignalIdentity(sprintf('trace.child.%02d', $index), 1), new AttributeSet([]));
        }
        self::assertSame(64, $capacity->activeSpanCount());
        $this->expectFailure(TracingLifecycleException::class, static fn() => $capacity->beginSpan(new SignalIdentity('trace.child.overflow', 1), new AttributeSet([])));
        $capacity->retire();
        self::assertSame(0, $capacity->activeSpanCount());
    }

    /**
     * @param list<SpanId> $spanIds
     * @param list<int> $ticks
     */
    private function operation(array $spanIds, array $ticks): \Kongono\Tracing\TraceOperation
    {
        return $this->runtime($spanIds, $ticks)->begin(
            ObservabilityContext::uncorrelated(),
            new SignalIdentity('trace.operation', 1),
            new AttributeSet([]),
        );
    }

    /**
     * @param list<SpanId> $spanIds
     * @param list<int> $ticks
     */
    private function runtime(array $spanIds, array $ticks): TraceRuntime
    {
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
        $clock = new class ($ticks) implements TraceClock {
            /** @param list<int> $ticks */
            public function __construct(private array $ticks) {}
            public function nowNanoseconds(): int
            {
                return array_shift($this->ticks) ?? 0;
            }
        };
        $sampler = new class implements TraceSampler {
            public function decide(\Kongono\Tracing\TraceSamplingRequest $request): TraceSamplingDecision
            {
                return TraceSamplingDecision::Drop;
            }
        };

        return new TraceRuntime($clock, $identifiers, $sampler, self::createStub(ObservationRecorder::class));
    }

    /** @param class-string<\Throwable> $class */
    private function expectFailure(string $class, callable $operation): void
    {
        try {
            $operation();
            self::fail('Expected tracing failure was not raised.');
        } catch (\Throwable $failure) {
            self::assertInstanceOf($class, $failure);
        }
    }
}
