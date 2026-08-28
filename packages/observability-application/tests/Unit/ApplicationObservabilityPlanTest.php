<?php

declare(strict_types=1);

namespace Kongono\ObservabilityApplication\Tests\Unit;

use Kongono\Metrics\MetricDimensionSchema;
use Kongono\Metrics\MetricInstrument;
use Kongono\Metrics\MetricInstrumentKind;
use Kongono\Metrics\MetricPlan;
use Kongono\Metrics\MetricUnit;
use Kongono\Observability\AttributeSet;
use Kongono\Observability\ObservabilityContext;
use Kongono\Observability\SignalIdentity;
use Kongono\ObservabilityApplication\ApplicationObservabilityPlan;
use Kongono\Tracing\SpanId;
use Kongono\Tracing\TraceId;
use Kongono\Tracing\TracePropagation;
use Kongono\Tracing\TraceSamplingDecision;
use PHPUnit\Framework\TestCase;

final class ApplicationObservabilityPlanTest extends TestCase
{
    public function testPlanRetainsExactlyTheSuppliedImmutableObjects(): void
    {
        $context = ObservabilityContext::uncorrelated();
        $identity = new SignalIdentity('trace.application.plan', 1);
        $attributes = new AttributeSet([]);
        $instrument = new MetricInstrument(
            new SignalIdentity('metric.application.plan', 1),
            MetricInstrumentKind::Counter,
            new MetricUnit('operations'),
            new MetricDimensionSchema([]),
        );
        $metrics = new MetricPlan([$instrument]);
        $propagation = new TracePropagation(
            new TraceId(str_repeat('1', 32)),
            new SpanId(str_repeat('2', 16)),
            TraceSamplingDecision::Record,
        );
        $plan = new ApplicationObservabilityPlan($context, $identity, $attributes, $metrics, $propagation);

        self::assertSame($context, $plan->context());
        self::assertSame($identity, $plan->rootTraceIdentity());
        self::assertSame($attributes, $plan->rootTraceAttributes());
        self::assertSame($metrics, $plan->metricPlan());
        self::assertSame($propagation, $plan->tracePropagation());
    }
}
