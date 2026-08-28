<?php

declare(strict_types=1);

namespace Kongono\Tracing;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Observability\AttributeSet;
use Kongono\Observability\ObservabilityContext;
use Kongono\Observability\ObservationRecorder;
use Kongono\Observability\SignalIdentity;
use Kongono\Tracing\Exception\ExceptionInterface;
use Kongono\Tracing\Exception\InvalidTracingValueException;
use Kongono\Tracing\Exception\TracingCollaboratorException;
use Kongono\Tracing\Internal\RecordingTraceOperation;
use Kongono\Tracing\Internal\TracingValidation;
use Throwable;

/** Begins isolated explicit trace operations through caller-owned capabilities. */
#[Api(ApiStatus::Experimental)]
final readonly class TraceRuntime
{
    public function __construct(
        private TraceClock $clock,
        private TraceIdentifierSource $identifiers,
        private TraceSampler $sampler,
        private ObservationRecorder $recorder,
    ) {}

    public function begin(
        ObservabilityContext $context,
        SignalIdentity $identity,
        AttributeSet $attributes,
        ?TracePropagation $parent = null,
    ): TraceOperation {
        TracingValidation::attributes($attributes);

        $traceId = $parent?->traceId() ?? $this->nextTraceId();
        $spanId = $this->nextSpanId();
        if (null !== $parent && $spanId->equals($parent->spanId())) {
            throw InvalidTracingValueException::identifier();
        }

        $request = new TraceSamplingRequest($context, $identity, $traceId, $parent);
        $decision = $this->sample($request);
        $start = $this->startTick();

        return new RecordingTraceOperation(
            $context,
            $identity,
            $attributes,
            $traceId,
            $spanId,
            $parent?->spanId(),
            $decision,
            $start,
            $this->clock,
            $this->identifiers,
            $this->sampler,
            $this->recorder,
        );
    }

    private function nextTraceId(): TraceId
    {
        try {
            return $this->identifiers->nextTraceId();
        } catch (ExceptionInterface $failure) {
            throw $failure;
        } catch (Throwable $failure) {
            throw TracingCollaboratorException::identifierSource($failure);
        }
    }

    private function nextSpanId(): SpanId
    {
        try {
            return $this->identifiers->nextSpanId();
        } catch (ExceptionInterface $failure) {
            throw $failure;
        } catch (Throwable $failure) {
            throw TracingCollaboratorException::identifierSource($failure);
        }
    }

    private function sample(TraceSamplingRequest $request): TraceSamplingDecision
    {
        try {
            return $this->sampler->decide($request);
        } catch (ExceptionInterface $failure) {
            throw $failure;
        } catch (Throwable $failure) {
            throw TracingCollaboratorException::sampler($failure);
        }
    }

    private function startTick(): int
    {
        try {
            $tick = $this->clock->nowNanoseconds();
        } catch (ExceptionInterface $failure) {
            throw $failure;
        } catch (Throwable $failure) {
            throw TracingCollaboratorException::clock($failure);
        }

        TracingValidation::startTick($tick);

        return $tick;
    }
}
