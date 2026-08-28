<?php

declare(strict_types=1);

namespace Kongono\ObservabilityApplication\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Metrics\MetricCollection;
use Kongono\Observability\ObservabilityContext;
use Kongono\ObservabilityApplication\ApplicationObservabilityOperationState;
use Kongono\ObservabilityApplication\Exception\ApplicationObservabilityLifecycleException;
use Kongono\OperationalEvents\OperationalEvent;
use Kongono\OperationalEvents\OperationalEventRuntime;
use Kongono\SecurityAudit\SecurityAuditEvidence;
use Kongono\SecurityAudit\SecurityAuditRuntime;
use Kongono\Tracing\TraceOperation;

/** Owns and releases the references behind one public operation facade. */
#[Api(ApiStatus::Internal)]
final class ApplicationObservabilityOperationBoundary
{
    private ApplicationObservabilityOperationState $state = ApplicationObservabilityOperationState::Active;

    public function __construct(
        private ?ObservabilityContext $context,
        private ?TraceOperation $traceOperation,
        private ?MetricCollection $metricCollection,
        private ?OperationalEventRuntime $operationalEvents,
        private ?SecurityAuditRuntime $securityAudit,
    ) {}

    public function state(): ApplicationObservabilityOperationState
    {
        return $this->state;
    }

    public function traceOperation(): TraceOperation
    {
        $this->requireActive();

        return $this->traceOperation ?? throw ApplicationObservabilityLifecycleException::operationState();
    }

    public function metricCollection(): MetricCollection
    {
        $this->requireActive();

        return $this->metricCollection ?? throw ApplicationObservabilityLifecycleException::operationState();
    }

    public function recordOperationalEvent(OperationalEvent $event): void
    {
        $this->requireActive();
        if ($event->context() !== $this->context) {
            throw ApplicationObservabilityLifecycleException::operationContext();
        }

        ($this->operationalEvents ?? throw ApplicationObservabilityLifecycleException::operationState())->record($event);
    }

    public function recordSecurityAudit(SecurityAuditEvidence $evidence): void
    {
        $this->requireActive();
        if ($evidence->context() !== $this->context) {
            throw ApplicationObservabilityLifecycleException::operationContext();
        }

        ($this->securityAudit ?? throw ApplicationObservabilityLifecycleException::operationState())->record($evidence);
    }

    public function beginCompletion(): void
    {
        $this->requireActive();
        $this->state = ApplicationObservabilityOperationState::Completing;
    }

    public function close(): void
    {
        if (ApplicationObservabilityOperationState::Closed === $this->state) {
            return;
        }

        $this->state = ApplicationObservabilityOperationState::Closed;
        $this->context = null;
        $this->traceOperation = null;
        $this->metricCollection = null;
        $this->operationalEvents = null;
        $this->securityAudit = null;
    }

    private function requireActive(): void
    {
        if (ApplicationObservabilityOperationState::Active !== $this->state) {
            throw ApplicationObservabilityLifecycleException::operationState();
        }
    }
}
