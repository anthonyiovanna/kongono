<?php

declare(strict_types=1);

namespace Kongono\Tracing;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Carries trusted typed trace propagation after caller-owned validation. */
#[Api(ApiStatus::Experimental)]
final readonly class TracePropagation
{
    public function __construct(
        private TraceId $traceId,
        private SpanId $spanId,
        private TraceSamplingDecision $samplingDecision,
    ) {}

    public function traceId(): TraceId
    {
        return $this->traceId;
    }

    public function spanId(): SpanId
    {
        return $this->spanId;
    }

    public function samplingDecision(): TraceSamplingDecision
    {
        return $this->samplingDecision;
    }
}
