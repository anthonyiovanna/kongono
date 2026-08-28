<?php

declare(strict_types=1);

namespace Kongono\Tracing\Tests\Unit;

use Kongono\Observability\ObservabilityContext;
use Kongono\Observability\SignalIdentity;
use Kongono\Tracing\SpanId;
use Kongono\Tracing\TraceId;
use Kongono\Tracing\TracePropagation;
use Kongono\Tracing\TraceSamplingDecision;
use Kongono\Tracing\TraceSamplingRequest;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class TracePropagationTest extends TestCase
{
    public function testPropagationAndSamplingRequestRetainExactTypedObjects(): void
    {
        $traceId = new TraceId(str_repeat('1', 32));
        $spanId = new SpanId(str_repeat('2', 16));
        $propagation = new TracePropagation($traceId, $spanId, TraceSamplingDecision::Drop);
        $context = ObservabilityContext::uncorrelated();
        $identity = new SignalIdentity('trace.root', 1);
        $request = new TraceSamplingRequest($context, $identity, $traceId, $propagation);

        self::assertSame($traceId, $propagation->traceId());
        self::assertSame($spanId, $propagation->spanId());
        self::assertSame(TraceSamplingDecision::Drop, $propagation->samplingDecision());
        self::assertSame($context, $request->context());
        self::assertSame($identity, $request->identity());
        self::assertSame($traceId, $request->traceId());
        self::assertSame($propagation, $request->parent());
        self::assertFalse((new ReflectionClass($propagation))->hasMethod('__serialize'));
        self::assertFalse((new ReflectionClass($request))->hasMethod('__serialize'));
    }
}
