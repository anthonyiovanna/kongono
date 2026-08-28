<?php

declare(strict_types=1);

namespace Kongono\OperationalEvents;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Observability\AttributeSet;
use Kongono\Observability\ObservabilityContext;
use Kongono\Observability\ObservationStatus;

/** Retains one schema-validated structured operational event. */
#[Api(ApiStatus::Experimental)]
final readonly class OperationalEvent
{
    private DiagnosticDisclosure $disclosure;

    public function __construct(
        private ObservabilityContext $context,
        private OperationalEventDefinition $definition,
        private ObservationStatus $status,
        private AttributeSet $attributes,
    ) {
        $this->disclosure = $definition->schema()->validate($attributes);
    }

    public function context(): ObservabilityContext
    {
        return $this->context;
    }

    public function definition(): OperationalEventDefinition
    {
        return $this->definition;
    }

    public function status(): ObservationStatus
    {
        return $this->status;
    }

    public function attributes(): AttributeSet
    {
        return $this->attributes;
    }

    public function disclosure(): DiagnosticDisclosure
    {
        return $this->disclosure;
    }
}
