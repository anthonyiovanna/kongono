<?php

declare(strict_types=1);

namespace Kongono\Tracing;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Observability\ObservationStatus;

/** Exposes one child span owned by exactly one trace operation. */
#[Api(ApiStatus::Experimental)]
interface TraceSpan
{
    public function traceId(): TraceId;

    public function spanId(): SpanId;

    public function parentSpanId(): SpanId;

    public function samplingDecision(): TraceSamplingDecision;

    public function state(): TraceLifecycleState;

    public function propagation(): TracePropagation;

    public function complete(ObservationStatus $status): void;

    public function retire(): void;
}
