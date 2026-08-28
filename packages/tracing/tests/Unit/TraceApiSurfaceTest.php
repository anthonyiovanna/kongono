<?php

declare(strict_types=1);

namespace Kongono\Tracing\Tests\Unit;

use Kongono\Tracing\SpanId;
use Kongono\Tracing\TraceClock;
use Kongono\Tracing\TraceId;
use Kongono\Tracing\TraceIdentifierSource;
use Kongono\Tracing\TraceLifecycleState;
use Kongono\Tracing\TraceOperation;
use Kongono\Tracing\TracePropagation;
use Kongono\Tracing\TraceRuntime;
use Kongono\Tracing\TraceSampler;
use Kongono\Tracing\TraceSamplingDecision;
use Kongono\Tracing\TraceSamplingRequest;
use Kongono\Tracing\TraceSpan;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class TraceApiSurfaceTest extends TestCase
{
    public function testEnumsAndDeclaredPublicSurfacesAreExact(): void
    {
        self::assertSame(['record', 'drop'], array_column(TraceSamplingDecision::cases(), 'value'));
        self::assertSame(['active', 'completed', 'retired'], array_column(TraceLifecycleState::cases(), 'value'));

        $expected = [
            TraceId::class => ['__construct', 'equals', 'value'],
            SpanId::class => ['__construct', 'equals', 'value'],
            TracePropagation::class => ['__construct', 'samplingDecision', 'spanId', 'traceId'],
            TraceSamplingRequest::class => ['__construct', 'context', 'identity', 'parent', 'traceId'],
            TraceClock::class => ['nowNanoseconds'],
            TraceIdentifierSource::class => ['nextSpanId', 'nextTraceId'],
            TraceSampler::class => ['decide'],
            TraceRuntime::class => ['__construct', 'begin'],
            TraceOperation::class => ['activeSpanCount', 'beginSpan', 'complete', 'parentSpanId', 'propagation', 'retire', 'samplingDecision', 'spanId', 'state', 'traceId'],
            TraceSpan::class => ['complete', 'parentSpanId', 'propagation', 'retire', 'samplingDecision', 'spanId', 'state', 'traceId'],
        ];

        foreach ($expected as $class => $methods) {
            self::assertSame($methods, $this->declaredPublicMethods(new ReflectionClass($class)), $class);
        }
    }

    /**
     * @template T of object
     * @param ReflectionClass<T> $reflection
     * @return list<string>
     */
    private function declaredPublicMethods(ReflectionClass $reflection): array
    {
        $methods = [];
        foreach ($reflection->getMethods() as $method) {
            if ($method->isPublic() && $method->getDeclaringClass()->getName() === $reflection->getName()) {
                $methods[] = $method->getName();
            }
        }
        sort($methods);

        return $methods;
    }
}
