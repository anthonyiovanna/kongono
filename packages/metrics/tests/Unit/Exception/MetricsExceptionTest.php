<?php

declare(strict_types=1);

namespace Kongono\Metrics\Tests\Unit\Exception;

use Kongono\Metrics\Exception\ExceptionInterface;
use Kongono\Metrics\Exception\InvalidMetricSchemaException;
use Kongono\Metrics\Exception\InvalidMetricValueException;
use Kongono\Metrics\Exception\MetricAggregationException;
use Kongono\Metrics\Exception\MetricLifecycleException;
use Kongono\Metrics\Exception\MetricRecordingException;
use Kongono\Metrics\Exception\MetricsException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

final class MetricsExceptionTest extends TestCase
{
    public function testCatchBoundaryBaseAndFactoriesAreExact(): void
    {
        $marker = new ReflectionClass(ExceptionInterface::class);
        $base = new ReflectionClass(MetricsException::class);
        self::assertContains(\Throwable::class, $marker->getInterfaceNames());
        self::assertSame(['diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'], $this->declaredPublicMethods($marker));
        self::assertTrue($base->isAbstract());
        self::assertSame([
            ['boundaries', 'measurement', 'unit'],
            ['dimension', 'instrument', 'plan'],
            ['ownership', 'state'],
            ['overflow'],
            ['failed'],
        ], array_map(fn(string $class): array => $this->declaredPublicMethods(new ReflectionClass($class)), [
            InvalidMetricValueException::class,
            InvalidMetricSchemaException::class,
            MetricLifecycleException::class,
            MetricAggregationException::class,
            MetricRecordingException::class,
        ]));
    }

    public function testEveryFailureIsEagerDeterministicAndNondisclosing(): void
    {
        $previous = new RuntimeException('protected-cause');
        $exceptions = [
            InvalidMetricValueException::unit(),
            InvalidMetricValueException::measurement(),
            InvalidMetricValueException::boundaries(),
            InvalidMetricSchemaException::dimension(),
            InvalidMetricSchemaException::instrument(),
            InvalidMetricSchemaException::plan(),
            MetricLifecycleException::state(),
            MetricLifecycleException::ownership(),
            MetricAggregationException::overflow(),
            MetricRecordingException::failed($previous),
        ];
        foreach ($exceptions as $exception) {
            self::assertSame($exception->diagnosticContext(), $exception->diagnosticContext());
            self::assertSame($exception->diagnosticId() . ': ' . $exception->diagnosticTitle(), (string) $exception);
            foreach (['protected-cause', 'tenant-secret', 'raw-series', 'INF'] as $raw) {
                self::assertStringNotContainsString($raw, $exception->getMessage());
                self::assertStringNotContainsString($raw, (string) $exception);
                foreach ($exception->diagnosticContext()->fields() as $field) {
                    self::assertNotSame($raw, $field->value());
                }
            }
        }
        self::assertSame($previous, $exceptions[9]->getPrevious());
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
