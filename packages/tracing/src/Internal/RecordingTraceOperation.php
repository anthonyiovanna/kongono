<?php

declare(strict_types=1);

namespace Kongono\Tracing\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Observability\AttributeSet;
use Kongono\Observability\ObservabilityContext;
use Kongono\Observability\ObservationRecorder;
use Kongono\Observability\ObservationStatus;
use Kongono\Observability\SignalIdentity;
use Kongono\Tracing\Exception\ExceptionInterface;
use Kongono\Tracing\Exception\InvalidTracingValueException;
use Kongono\Tracing\Exception\TraceRecordingException;
use Kongono\Tracing\Exception\TracingCollaboratorException;
use Kongono\Tracing\Exception\TracingLifecycleException;
use Kongono\Tracing\SpanId;
use Kongono\Tracing\TraceClock;
use Kongono\Tracing\TraceId;
use Kongono\Tracing\TraceIdentifierSource;
use Kongono\Tracing\TraceLifecycleState;
use Kongono\Tracing\TraceOperation;
use Kongono\Tracing\TracePropagation;
use Kongono\Tracing\TraceSampler;
use Kongono\Tracing\TraceSamplingDecision;
use Kongono\Tracing\TraceSpan;
use Throwable;

/** Owns one isolated root trace span and its bounded active child set. */
#[Api(ApiStatus::Internal)]
final class RecordingTraceOperation implements TraceOperation
{
    private TraceLifecycleState $state = TraceLifecycleState::Active;

    private ?ObservabilityContext $context;

    private ?SignalIdentity $identity;

    private ?AttributeSet $attributes;

    private ?int $start;

    private ?TraceClock $clock;

    private ?TraceIdentifierSource $identifiers;

    private ?TraceSampler $sampler;

    private ?ObservationRecorder $recorder;

    /** @var list<RecordingTraceSpan> */
    private array $activeSpans = [];

    /** @var array<string, true> */
    private array $usedSpanIds;

    public function __construct(
        ObservabilityContext $context,
        SignalIdentity $identity,
        AttributeSet $attributes,
        private readonly TraceId $traceId,
        private readonly SpanId $spanId,
        private readonly ?SpanId $parentSpanId,
        private readonly TraceSamplingDecision $samplingDecision,
        int $start,
        TraceClock $clock,
        TraceIdentifierSource $identifiers,
        TraceSampler $sampler,
        ObservationRecorder $recorder,
    ) {
        $this->context = $context;
        $this->identity = $identity;
        $this->attributes = $attributes;
        $this->start = $start;
        $this->clock = $clock;
        $this->identifiers = $identifiers;
        $this->sampler = $sampler;
        $this->recorder = $recorder;
        $this->usedSpanIds = [$spanId->value() => true];
        if (null !== $parentSpanId) {
            $this->usedSpanIds[$parentSpanId->value()] = true;
        }
    }

    public function traceId(): TraceId
    {
        return $this->traceId;
    }

    public function spanId(): SpanId
    {
        return $this->spanId;
    }

    public function parentSpanId(): ?SpanId
    {
        return $this->parentSpanId;
    }

    public function samplingDecision(): TraceSamplingDecision
    {
        return $this->samplingDecision;
    }

    public function state(): TraceLifecycleState
    {
        return $this->state;
    }

    public function propagation(): TracePropagation
    {
        $this->requireActive();

        return new TracePropagation($this->traceId, $this->spanId, $this->samplingDecision);
    }

    public function beginSpan(SignalIdentity $identity, AttributeSet $attributes, ?TraceSpan $parent = null): TraceSpan
    {
        $this->requireActive();
        TracingValidation::attributes($attributes);
        if (64 === count($this->activeSpans)) {
            throw TracingLifecycleException::capacity();
        }

        $parentSpanId = $this->spanId;
        if (null !== $parent) {
            if (!$parent instanceof RecordingTraceSpan || !$this->isActiveSpan($parent)) {
                throw TracingLifecycleException::ownership();
            }
            $parentSpanId = $parent->spanId();
        }

        $spanId = $this->nextSpanId();
        if (isset($this->usedSpanIds[$spanId->value()])) {
            throw InvalidTracingValueException::identifier();
        }
        $start = $this->readStartTick();

        $span = new RecordingTraceSpan(
            $this,
            $identity,
            $attributes,
            $this->traceId,
            $spanId,
            $parentSpanId,
            $this->samplingDecision,
            $start,
        );
        $this->usedSpanIds[$spanId->value()] = true;
        $this->activeSpans[] = $span;

        return $span;
    }

    public function complete(ObservationStatus $status): void
    {
        $this->requireActive();
        if ([] !== $this->activeSpans) {
            throw TracingLifecycleException::state();
        }
        TracingValidation::status($status);
        if (null === $this->sampler) {
            throw TracingLifecycleException::state();
        }

        $start = $this->start ?? throw TracingLifecycleException::state();
        $end = $this->readEndTick($start);
        $context = $this->context ?? throw TracingLifecycleException::state();
        $identity = $this->identity ?? throw TracingLifecycleException::state();
        $attributes = $this->attributes ?? throw TracingLifecycleException::state();
        $recorder = $this->recorder ?? throw TracingLifecycleException::state();

        $this->state = TraceLifecycleState::Completed;
        $this->releaseOwnedEvidence();

        if (TraceSamplingDecision::Drop === $this->samplingDecision) {
            return;
        }

        $observation = TraceObservationFactory::create(
            $context,
            $identity,
            $attributes,
            $this->traceId,
            $this->spanId,
            $this->parentSpanId,
            $start,
            $end,
            $status,
        );
        self::record($recorder, $observation);
    }

    public function retire(): void
    {
        if (TraceLifecycleState::Active !== $this->state) {
            return;
        }

        $spans = $this->activeSpans;
        foreach ($spans as $span) {
            $span->retire();
        }
        $this->state = TraceLifecycleState::Retired;
        $this->releaseOwnedEvidence();
    }

    public function activeSpanCount(): int
    {
        return count($this->activeSpans);
    }

    public function isActiveSpan(RecordingTraceSpan $span): bool
    {
        if (TraceLifecycleState::Active !== $this->state || TraceLifecycleState::Active !== $span->state()) {
            return false;
        }

        foreach ($this->activeSpans as $activeSpan) {
            if ($activeSpan === $span) {
                return true;
            }
        }

        return false;
    }

    public function unregister(RecordingTraceSpan $span): void
    {
        foreach ($this->activeSpans as $index => $activeSpan) {
            if ($activeSpan === $span) {
                array_splice($this->activeSpans, $index, 1);

                return;
            }
        }
    }

    public function readEndTick(int $start): int
    {
        $this->requireActive();
        $clock = $this->clock ?? throw TracingLifecycleException::state();
        try {
            $end = $clock->nowNanoseconds();
        } catch (ExceptionInterface $failure) {
            throw $failure;
        } catch (Throwable $failure) {
            throw TracingCollaboratorException::clock($failure);
        }

        TracingValidation::endTick($start, $end);

        return $end;
    }

    /** @return array{ObservabilityContext, ObservationRecorder} */
    public function recordingEvidence(): array
    {
        $this->requireActive();

        return [
            $this->context ?? throw TracingLifecycleException::state(),
            $this->recorder ?? throw TracingLifecycleException::state(),
        ];
    }

    public static function record(ObservationRecorder $recorder, \Kongono\Observability\Observation $observation): void
    {
        try {
            $recorder->record($observation);
        } catch (ExceptionInterface $failure) {
            throw $failure;
        } catch (Throwable $failure) {
            throw TraceRecordingException::failed($failure);
        }
    }

    private function requireActive(): void
    {
        if (TraceLifecycleState::Active !== $this->state) {
            throw TracingLifecycleException::state();
        }
    }

    private function nextSpanId(): SpanId
    {
        $identifiers = $this->identifiers ?? throw TracingLifecycleException::state();
        try {
            return $identifiers->nextSpanId();
        } catch (ExceptionInterface $failure) {
            throw $failure;
        } catch (Throwable $failure) {
            throw TracingCollaboratorException::identifierSource($failure);
        }
    }

    private function readStartTick(): int
    {
        $clock = $this->clock ?? throw TracingLifecycleException::state();
        try {
            $start = $clock->nowNanoseconds();
        } catch (ExceptionInterface $failure) {
            throw $failure;
        } catch (Throwable $failure) {
            throw TracingCollaboratorException::clock($failure);
        }

        TracingValidation::startTick($start);

        return $start;
    }

    private function releaseOwnedEvidence(): void
    {
        $this->context = null;
        $this->identity = null;
        $this->attributes = null;
        $this->start = null;
        $this->clock = null;
        $this->identifiers = null;
        $this->sampler = null;
        $this->recorder = null;
        $this->activeSpans = [];
        $this->usedSpanIds = [];
    }
}
