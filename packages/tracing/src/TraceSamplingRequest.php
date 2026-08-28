<?php

declare(strict_types=1);

namespace Kongono\Tracing;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Observability\ObservabilityContext;
use Kongono\Observability\SignalIdentity;

/** Supplies exact explicit facts to one caller-owned trace sampling policy. */
#[Api(ApiStatus::Experimental)]
final readonly class TraceSamplingRequest
{
    public function __construct(
        private ObservabilityContext $context,
        private SignalIdentity $identity,
        private TraceId $traceId,
        private ?TracePropagation $parent,
    ) {}

    public function context(): ObservabilityContext
    {
        return $this->context;
    }

    public function identity(): SignalIdentity
    {
        return $this->identity;
    }

    public function traceId(): TraceId
    {
        return $this->traceId;
    }

    public function parent(): ?TracePropagation
    {
        return $this->parent;
    }
}
