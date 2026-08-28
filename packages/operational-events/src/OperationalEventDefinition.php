<?php

declare(strict_types=1);

namespace Kongono\OperationalEvents;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Observability\SignalIdentity;

/** Defines one exact operational-event identity, severity, and field schema. */
#[Api(ApiStatus::Experimental)]
final readonly class OperationalEventDefinition
{
    public function __construct(
        private SignalIdentity $identity,
        private OperationalEventSeverity $severity,
        private OperationalEventSchema $schema,
    ) {}

    public function identity(): SignalIdentity
    {
        return $this->identity;
    }

    public function severity(): OperationalEventSeverity
    {
        return $this->severity;
    }

    public function schema(): OperationalEventSchema
    {
        return $this->schema;
    }
}
