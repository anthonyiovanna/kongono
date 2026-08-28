<?php

declare(strict_types=1);

namespace Kongono\ObservabilityApplication\Tests\Fixture;

use Kongono\Metrics\MetricDimensionSchema;
use Kongono\Metrics\MetricInstrument;
use Kongono\Metrics\MetricInstrumentKind;
use Kongono\Metrics\MetricPlan;
use Kongono\Metrics\MetricRuntime;
use Kongono\Metrics\MetricUnit;
use Kongono\Observability\AttributeSet;
use Kongono\Observability\ObservabilityContext;
use Kongono\Observability\SignalIdentity;
use Kongono\ObservabilityApplication\ApplicationObservability;
use Kongono\ObservabilityApplication\ApplicationObservabilityPlan;
use Kongono\OpenTelemetry\OpenTelemetryBatchPolicy;
use Kongono\OpenTelemetry\OpenTelemetryBridge;
use Kongono\OpenTelemetry\OpenTelemetryResource;
use Kongono\OpenTelemetry\OpenTelemetryTimeAnchor;
use Kongono\OperationalEvents\OperationalEventRuntime;
use Kongono\SecurityAudit\SecurityAuditRuntime;
use Kongono\Tracing\TraceRuntime;

final class ObservabilityApplicationFixture
{
    /** @return array{ApplicationObservability, TestOtlpTransport} */
    public static function application(int $queueCapacity = 64, ?TestOtlpTransport $transport = null): array
    {
        $transport ??= new TestOtlpTransport();
        $bridge = new OpenTelemetryBridge(
            new OpenTelemetryResource('observability-application-test'),
            new OpenTelemetryBatchPolicy($queueCapacity, $queueCapacity, 100),
            new OpenTelemetryTimeAnchor(0, 1),
            new TestOpenTelemetryClock(),
            $transport,
        );

        return [
            new ApplicationObservability(
                $bridge,
                new TraceRuntime(new TestTraceClock(), new TestTraceIdentifierSource(), new TestTraceSampler(), $bridge),
                new MetricRuntime($bridge),
                new OperationalEventRuntime($bridge),
                new SecurityAuditRuntime($bridge),
            ),
            $transport,
        ];
    }

    public static function plan(?ObservabilityContext $context = null): ApplicationObservabilityPlan
    {
        $instrument = new MetricInstrument(
            new SignalIdentity('metric.application.operations', 1),
            MetricInstrumentKind::Counter,
            new MetricUnit('operations'),
            new MetricDimensionSchema([]),
        );

        return new ApplicationObservabilityPlan(
            $context ?? ObservabilityContext::uncorrelated(),
            new SignalIdentity('trace.application.operation', 1),
            new AttributeSet([]),
            new MetricPlan([$instrument]),
        );
    }

    private function __construct() {}
}
