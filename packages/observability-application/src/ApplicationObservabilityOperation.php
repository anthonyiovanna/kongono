<?php

declare(strict_types=1);

namespace Kongono\ObservabilityApplication;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Metrics\MetricCollection;
use Kongono\ObservabilityApplication\Internal\ApplicationObservabilityOperationBoundary;
use Kongono\OperationalEvents\OperationalEvent;
use Kongono\SecurityAudit\SecurityAuditEvidence;
use Kongono\Tracing\TraceOperation;

/** Exposes only the exact signal handles and record operations for one active unit of work. */
#[Api(ApiStatus::Experimental)]
final readonly class ApplicationObservabilityOperation
{
    public function __construct(private ApplicationObservabilityOperationBoundary $boundary) {}

    public function traceOperation(): TraceOperation
    {
        return $this->boundary->traceOperation();
    }

    public function metricCollection(): MetricCollection
    {
        return $this->boundary->metricCollection();
    }

    public function recordOperationalEvent(OperationalEvent $event): void
    {
        $this->boundary->recordOperationalEvent($event);
    }

    public function recordSecurityAudit(SecurityAuditEvidence $evidence): void
    {
        $this->boundary->recordSecurityAudit($evidence);
    }

    public function state(): ApplicationObservabilityOperationState
    {
        return $this->boundary->state();
    }
}
