<?php

declare(strict_types=1);

namespace Kongono\ObservabilityApplication;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Metrics\MetricRuntime;
use Kongono\Observability\ObservationStatus;
use Kongono\ObservabilityApplication\Exception\ApplicationObservabilityCleanupException;
use Kongono\ObservabilityApplication\Exception\ApplicationObservabilityLifecycleException;
use Kongono\ObservabilityApplication\Internal\ApplicationObservabilityOperationBoundary;
use Kongono\OpenTelemetry\OpenTelemetryBridge;
use Kongono\OperationalEvents\OperationalEventRuntime;
use Kongono\SecurityAudit\SecurityAuditRuntime;
use Kongono\Tracing\TraceRuntime;
use Throwable;

/** Executes isolated observable work and owns explicit bridge flush and close lifecycle. */
#[Api(ApiStatus::Experimental)]
final class ApplicationObservability
{
    private const int ACTIVE_OPERATION_CAPACITY = 64;

    private bool $closed = false;

    private int $activeOperations = 0;

    public function __construct(
        private readonly OpenTelemetryBridge $bridge,
        private readonly TraceRuntime $tracing,
        private readonly MetricRuntime $metrics,
        private readonly OperationalEventRuntime $operationalEvents,
        private readonly SecurityAuditRuntime $securityAudit,
    ) {}

    private function __clone(): void {}

    /**
     * @template TResult
     * @param ApplicationObservabilityWork<TResult> $work
     * @return TResult
     */
    public function execute(ApplicationObservabilityPlan $plan, ApplicationObservabilityWork $work): mixed
    {
        $this->reserveOperation();
        try {
            $trace = $this->tracing->begin(
                $plan->context(),
                $plan->rootTraceIdentity(),
                $plan->rootTraceAttributes(),
                $plan->tracePropagation(),
            );
            try {
                $metrics = $this->metrics->begin($plan->context(), $plan->metricPlan());
            } catch (Throwable $failure) {
                $trace->retire();

                throw $failure;
            }

            $boundary = new ApplicationObservabilityOperationBoundary(
                $plan->context(),
                $trace,
                $metrics,
                $this->operationalEvents,
                $this->securityAudit,
            );
            $operation = new ApplicationObservabilityOperation($boundary);
            $result = null;
            $primaryFailure = null;
            try {
                $result = $work->execute($operation);
            } catch (Throwable $failure) {
                $primaryFailure = $failure;
            }

            $boundary->beginCompletion();
            $cleanupFailures = [];
            try {
                $metrics->collect();
            } catch (Throwable $failure) {
                $cleanupFailures[] = $failure;
            } finally {
                $metrics->retire();
            }

            try {
                $trace->complete(null === $primaryFailure ? ObservationStatus::Ok : ObservationStatus::Error);
            } catch (Throwable $failure) {
                $cleanupFailures[] = $failure;
            } finally {
                $trace->retire();
            }

            try {
                $this->bridge->flush();
            } catch (Throwable $failure) {
                $cleanupFailures[] = $failure;
            }

            $boundary->close();
            if ([] !== $cleanupFailures) {
                throw ApplicationObservabilityCleanupException::forFailures($primaryFailure, $cleanupFailures);
            }
            if (null !== $primaryFailure) {
                throw $primaryFailure;
            }

            return $result;
        } finally {
            --$this->activeOperations;
        }
    }

    public function flush(): void
    {
        $this->requireOpen();
        if (0 !== $this->activeOperations) {
            throw ApplicationObservabilityLifecycleException::activeOperations();
        }

        $this->bridge->flush();
    }

    public function close(): void
    {
        if ($this->closed) {
            return;
        }
        if (0 !== $this->activeOperations) {
            throw ApplicationObservabilityLifecycleException::activeOperations();
        }

        $this->closed = true;
        $this->bridge->close();
    }

    private function reserveOperation(): void
    {
        $this->requireOpen();
        if (self::ACTIVE_OPERATION_CAPACITY === $this->activeOperations) {
            throw ApplicationObservabilityLifecycleException::operationCapacity();
        }

        ++$this->activeOperations;
    }

    private function requireOpen(): void
    {
        if ($this->closed) {
            throw ApplicationObservabilityLifecycleException::applicationClosed();
        }
    }
}
