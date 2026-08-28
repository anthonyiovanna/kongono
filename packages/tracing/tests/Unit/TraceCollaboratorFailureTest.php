<?php

declare(strict_types=1);

namespace Kongono\Tracing\Tests\Unit;

use Kongono\Observability\AttributeSet;
use Kongono\Observability\ObservabilityContext;
use Kongono\Observability\Observation;
use Kongono\Observability\ObservationRecorder;
use Kongono\Observability\ObservationStatus;
use Kongono\Observability\SignalIdentity;
use Kongono\Tracing\Exception\TraceRecordingException;
use Kongono\Tracing\Exception\TracingCollaboratorException;
use Kongono\Tracing\SpanId;
use Kongono\Tracing\TraceClock;
use Kongono\Tracing\TraceId;
use Kongono\Tracing\TraceIdentifierSource;
use Kongono\Tracing\TraceLifecycleState;
use Kongono\Tracing\TraceRuntime;
use Kongono\Tracing\TraceSampler;
use Kongono\Tracing\TraceSamplingDecision;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class TraceCollaboratorFailureTest extends TestCase
{
    public function testIdentifierSamplerAndClockFailuresPreserveExactCause(): void
    {
        $marker = new RuntimeException('private collaborator failure');
        $recorder = self::createStub(ObservationRecorder::class);

        $identifierFailure = new class ($marker) implements TraceIdentifierSource {
            public function __construct(private RuntimeException $failure) {}
            public function nextTraceId(): TraceId
            {
                throw $this->failure;
            }
            public function nextSpanId(): SpanId
            {
                return new SpanId(str_repeat('2', 16));
            }
        };
        try {
            $this->begin(self::createStub(TraceClock::class), $identifierFailure, self::createStub(TraceSampler::class), $recorder);
            self::fail('Identifier failure was hidden.');
        } catch (TracingCollaboratorException $exception) {
            self::assertSame($marker, $exception->getPrevious());
        }

        $identifiers = $this->identifiers();
        $samplerFailure = new class ($marker) implements TraceSampler {
            public function __construct(private RuntimeException $failure) {}
            public function decide(\Kongono\Tracing\TraceSamplingRequest $request): TraceSamplingDecision
            {
                throw $this->failure;
            }
        };
        try {
            $this->begin(self::createStub(TraceClock::class), $identifiers, $samplerFailure, $recorder);
            self::fail('Sampler failure was hidden.');
        } catch (TracingCollaboratorException $exception) {
            self::assertSame($marker, $exception->getPrevious());
        }

        $clockFailure = new class ($marker) implements TraceClock {
            public function __construct(private RuntimeException $failure) {}
            public function nowNanoseconds(): int
            {
                throw $this->failure;
            }
        };
        $sampler = self::createStub(TraceSampler::class);
        $sampler->method('decide')->willReturn(TraceSamplingDecision::Drop);
        try {
            $this->begin($clockFailure, $this->identifiers(), $sampler, $recorder);
            self::fail('Clock failure was hidden.');
        } catch (TracingCollaboratorException $exception) {
            self::assertSame($marker, $exception->getPrevious());
            self::assertStringNotContainsString($marker->getMessage(), (string) $exception);
        }
    }

    public function testRecorderFailureIsVisibleFinalAndNeverRetried(): void
    {
        $marker = new RuntimeException('private recorder failure');
        $clock = $this->createMock(TraceClock::class);
        $clock->expects(self::exactly(2))->method('nowNanoseconds')->willReturnOnConsecutiveCalls(1, 2);
        $sampler = self::createStub(TraceSampler::class);
        $sampler->method('decide')->willReturn(TraceSamplingDecision::Record);
        $recorder = new class ($marker) implements ObservationRecorder {
            public int $calls = 0;
            public function __construct(private RuntimeException $failure) {}
            public function record(Observation $observation): void
            {
                ++$this->calls;
                throw $this->failure;
            }
        };
        $operation = $this->begin($clock, $this->identifiers(), $sampler, $recorder);

        try {
            $operation->complete(ObservationStatus::Ok);
            self::fail('Recording failure was hidden.');
        } catch (TraceRecordingException $exception) {
            self::assertSame($marker, $exception->getPrevious());
            self::assertSame(TraceLifecycleState::Completed, $operation->state());
            self::assertSame(1, $recorder->calls);
        }

        $operation->retire();
        self::assertSame(1, $recorder->calls);
    }

    private function begin(TraceClock $clock, TraceIdentifierSource $identifiers, TraceSampler $sampler, ObservationRecorder $recorder): \Kongono\Tracing\TraceOperation
    {
        return (new TraceRuntime($clock, $identifiers, $sampler, $recorder))->begin(
            ObservabilityContext::uncorrelated(),
            new SignalIdentity('trace.failure', 1),
            new AttributeSet([]),
        );
    }

    private function identifiers(): TraceIdentifierSource
    {
        return new class implements TraceIdentifierSource {
            public function nextTraceId(): TraceId
            {
                return new TraceId(str_repeat('1', 32));
            }
            public function nextSpanId(): SpanId
            {
                return new SpanId(str_repeat('2', 16));
            }
        };
    }
}
