<?php

declare(strict_types=1);

namespace KongonoObservabilityMinimal;

use Kongono\Metrics\MetricInstrument;
use Kongono\Metrics\MetricMeasurement;
use Kongono\Metrics\MetricNumber;
use Kongono\Observability\AttributeSet;
use Kongono\Observability\ObservabilityContext;
use Kongono\Observability\ObservationStatus;
use Kongono\ObservabilityApplication\ApplicationObservabilityOperation;
use Kongono\ObservabilityApplication\ApplicationObservabilityWork;
use Kongono\OperationalEvents\OperationalEvent;
use Kongono\OperationalEvents\OperationalEventDefinition;
use Kongono\SecurityAudit\SecurityAuditDefinition;
use Kongono\SecurityAudit\SecurityAuditEvidence;
use Kongono\SecurityAudit\SecurityAuditOutcome;

/**
 * Records one metric, operational event, and distinct security-audit evidence item.
 *
 * @implements ApplicationObservabilityWork<string>
 */
final readonly class ExampleObservabilityWork implements ApplicationObservabilityWork
{
    public function __construct(
        private ObservabilityContext $context,
        private MetricInstrument $instrument,
        private OperationalEventDefinition $eventDefinition,
        private SecurityAuditDefinition $auditDefinition,
        private AttributeSet $auditAttributes,
    ) {}

    public function execute(ApplicationObservabilityOperation $operation): string
    {
        $operation->metricCollection()->record(
            $this->instrument,
            new MetricMeasurement(new MetricNumber(1), new AttributeSet([])),
        );
        $operation->recordOperationalEvent(new OperationalEvent(
            $this->context,
            $this->eventDefinition,
            ObservationStatus::Ok,
            new AttributeSet([]),
        ));
        $operation->recordSecurityAudit(new SecurityAuditEvidence(
            $this->context,
            $this->auditDefinition,
            SecurityAuditOutcome::Succeeded,
            $this->auditAttributes,
        ));

        return 'observability-example-ok';
    }
}
