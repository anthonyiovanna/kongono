<?php

declare(strict_types=1);

namespace Kongono\Observability;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Carries one neutral immutable observation without pipeline behavior. */
#[Api(ApiStatus::Experimental)]
final readonly class Observation
{
    public function __construct(
        private ObservabilityContext $context,
        private SignalIdentity $identity,
        private ObservationKind $kind,
        private ObservationStatus $status,
        private AttributeSet $attributes,
    ) {}

    public function context(): ObservabilityContext
    {
        return $this->context;
    }

    public function identity(): SignalIdentity
    {
        return $this->identity;
    }

    public function kind(): ObservationKind
    {
        return $this->kind;
    }

    public function status(): ObservationStatus
    {
        return $this->status;
    }

    public function attributes(): AttributeSet
    {
        return $this->attributes;
    }
}
