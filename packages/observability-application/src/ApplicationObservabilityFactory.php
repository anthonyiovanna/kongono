<?php

declare(strict_types=1);

namespace Kongono\ObservabilityApplication;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Metrics\MetricRuntime;
use Kongono\ObservabilityBuild\Workflow\CurrentObservabilityConfiguration;
use Kongono\OpenTelemetry\OpenTelemetryBridge;
use Kongono\OpenTelemetry\OpenTelemetryClock;
use Kongono\OpenTelemetry\OpenTelemetryTimeAnchor;
use Kongono\OpenTelemetry\OtlpHttpTransport;
use Kongono\OperationalEvents\OperationalEventRuntime;
use Kongono\SecurityAudit\SecurityAuditRuntime;
use Kongono\Tracing\TraceClock;
use Kongono\Tracing\TraceIdentifierSource;
use Kongono\Tracing\TraceRuntime;
use Kongono\Tracing\TraceSampler;

/** Constructs the exact supported observability bridge and signal runtime set. */
#[Api(ApiStatus::Experimental)]
final class ApplicationObservabilityFactory
{
    public function create(
        CurrentObservabilityConfiguration $configuration,
        TraceClock $traceClock,
        TraceIdentifierSource $traceIdentifiers,
        TraceSampler $traceSampler,
        OpenTelemetryTimeAnchor $timeAnchor,
        OpenTelemetryClock $openTelemetryClock,
        OtlpHttpTransport $transport,
    ): ApplicationObservability {
        $bridge = new OpenTelemetryBridge(
            $configuration->resource(),
            $configuration->batchPolicy(),
            $timeAnchor,
            $openTelemetryClock,
            $transport,
        );

        return new ApplicationObservability(
            $bridge,
            new TraceRuntime($traceClock, $traceIdentifiers, $traceSampler, $bridge),
            new MetricRuntime($bridge),
            new OperationalEventRuntime($bridge),
            new SecurityAuditRuntime($bridge),
        );
    }
}
