<?php

declare(strict_types=1);

namespace Kongono\Tracing;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Observability\AttributeSet;
use Kongono\Observability\ObservationStatus;
use Kongono\Observability\SignalIdentity;

/** Exposes one explicit operation-owned root span and child-span lifecycle. */
#[Api(ApiStatus::Experimental)]
interface TraceOperation
{
    public function traceId(): TraceId;

    public function spanId(): SpanId;

    public function parentSpanId(): ?SpanId;

    public function samplingDecision(): TraceSamplingDecision;

    public function state(): TraceLifecycleState;

    public function propagation(): TracePropagation;

    public function beginSpan(SignalIdentity $identity, AttributeSet $attributes, ?TraceSpan $parent = null): TraceSpan;

    public function complete(ObservationStatus $status): void;

    public function retire(): void;

    public function activeSpanCount(): int;
}
