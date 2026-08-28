<?php

declare(strict_types=1);

namespace Kongono\Tracing\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Observability\AttributeSet;
use Kongono\Observability\ObservationStatus;
use Kongono\Observability\SignalIdentity;
use Kongono\Tracing\Exception\TracingLifecycleException;
use Kongono\Tracing\SpanId;
use Kongono\Tracing\TraceId;
use Kongono\Tracing\TraceLifecycleState;
use Kongono\Tracing\TracePropagation;
use Kongono\Tracing\TraceSamplingDecision;
use Kongono\Tracing\TraceSpan;

/** Owns one child-span lifecycle within exactly one trace operation. */
#[Api(ApiStatus::Internal)]
final class RecordingTraceSpan implements TraceSpan
{
    private TraceLifecycleState $state = TraceLifecycleState::Active;

    private ?RecordingTraceOperation $owner;

    private ?SignalIdentity $identity;

    private ?AttributeSet $attributes;

    private ?int $start;

    public function __construct(
        RecordingTraceOperation $owner,
        SignalIdentity $identity,
        AttributeSet $attributes,
        private readonly TraceId $traceId,
        private readonly SpanId $spanId,
        private readonly SpanId $parentSpanId,
        private readonly TraceSamplingDecision $samplingDecision,
        int $start,
    ) {
        $this->owner = $owner;
        $this->identity = $identity;
        $this->attributes = $attributes;
        $this->start = $start;
    }

    public function traceId(): TraceId
    {
        return $this->traceId;
    }

    public function spanId(): SpanId
    {
        return $this->spanId;
    }

    public function parentSpanId(): SpanId
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
        $owner = $this->requireActiveOwner();
        if (!$owner->isActiveSpan($this)) {
            throw TracingLifecycleException::ownership();
        }

        return new TracePropagation($this->traceId, $this->spanId, $this->samplingDecision);
    }

    public function complete(ObservationStatus $status): void
    {
        $owner = $this->requireActiveOwner();
        if (!$owner->isActiveSpan($this)) {
            throw TracingLifecycleException::ownership();
        }
        TracingValidation::status($status);

        $identity = $this->identity ?? throw TracingLifecycleException::state();
        $attributes = $this->attributes ?? throw TracingLifecycleException::state();
        $start = $this->start ?? throw TracingLifecycleException::state();
        $end = $owner->readEndTick($start);
        $recordingEvidence = TraceSamplingDecision::Record === $this->samplingDecision
            ? $owner->recordingEvidence()
            : null;

        $owner->unregister($this);
        $this->state = TraceLifecycleState::Completed;
        $this->releaseOwnedEvidence();

        if (null === $recordingEvidence) {
            return;
        }

        [$context, $recorder] = $recordingEvidence;
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
        RecordingTraceOperation::record($recorder, $observation);
    }

    public function retire(): void
    {
        if (TraceLifecycleState::Active !== $this->state) {
            return;
        }

        $this->owner?->unregister($this);
        $this->state = TraceLifecycleState::Retired;
        $this->releaseOwnedEvidence();
    }

    private function requireActiveOwner(): RecordingTraceOperation
    {
        if (TraceLifecycleState::Active !== $this->state || null === $this->owner) {
            throw TracingLifecycleException::state();
        }

        return $this->owner;
    }

    private function releaseOwnedEvidence(): void
    {
        $this->owner = null;
        $this->identity = null;
        $this->attributes = null;
        $this->start = null;
    }
}
