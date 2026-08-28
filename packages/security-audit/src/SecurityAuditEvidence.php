<?php

declare(strict_types=1);

namespace Kongono\SecurityAudit;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Observability\AttributeSet;
use Kongono\Observability\ObservabilityContext;

/** Retains one schema-validated bounded security-audit evidence record. */
#[Api(ApiStatus::Experimental)]
final readonly class SecurityAuditEvidence
{
    private DiagnosticDisclosure $disclosure;

    public function __construct(
        private ObservabilityContext $context,
        private SecurityAuditDefinition $definition,
        private SecurityAuditOutcome $outcome,
        private AttributeSet $attributes,
    ) {
        $this->disclosure = $definition->schema()->validate($attributes);
    }

    public function context(): ObservabilityContext
    {
        return $this->context;
    }

    public function definition(): SecurityAuditDefinition
    {
        return $this->definition;
    }

    public function outcome(): SecurityAuditOutcome
    {
        return $this->outcome;
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
