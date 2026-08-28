<?php

declare(strict_types=1);

namespace Kongono\Metrics\Tests\Unit;

use Kongono\Metrics\HistogramBoundaries;
use Kongono\Metrics\MetricCollection;
use Kongono\Metrics\MetricCollectionState;
use Kongono\Metrics\MetricDimension;
use Kongono\Metrics\MetricDimensionSchema;
use Kongono\Metrics\MetricInstrument;
use Kongono\Metrics\MetricInstrumentKind;
use Kongono\Metrics\MetricMeasurement;
use Kongono\Metrics\MetricNumber;
use Kongono\Metrics\MetricPlan;
use Kongono\Metrics\MetricRuntime;
use Kongono\Metrics\MetricUnit;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class MetricApiSurfaceTest extends TestCase
{
    public function testEnumsAndDeclaredPublicSurfacesAreExact(): void
    {
        self::assertSame(['counter', 'up_down_counter', 'gauge', 'histogram'], array_column(MetricInstrumentKind::cases(), 'value'));
        self::assertSame(['active', 'collected', 'retired'], array_column(MetricCollectionState::cases(), 'value'));
        $expected = [
            MetricNumber::class => ['__construct', 'equals', 'isFloat', 'isInteger', 'value'],
            MetricUnit::class => ['__construct', 'equals', 'value'],
            MetricDimension::class => ['__construct', 'allowedValues', 'key', 'matches'],
            MetricDimensionSchema::class => ['__construct', 'dimensions', 'seriesCapacity'],
            HistogramBoundaries::class => ['__construct', 'boundaries', 'count'],
            MetricInstrument::class => ['__construct', 'boundaries', 'dimensions', 'identity', 'kind', 'unit'],
            MetricMeasurement::class => ['__construct', 'dimensions', 'value'],
            MetricPlan::class => ['__construct', 'instruments', 'seriesCapacity'],
            MetricRuntime::class => ['__construct', 'begin'],
            MetricCollection::class => ['activeSeriesCount', 'collect', 'record', 'retire', 'state'],
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
