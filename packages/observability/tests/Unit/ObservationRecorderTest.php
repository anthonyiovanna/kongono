<?php

declare(strict_types=1);

namespace Kongono\Observability\Tests\Unit;

use Kongono\Observability\AttributeSet;
use Kongono\Observability\ObservabilityContext;
use Kongono\Observability\Observation;
use Kongono\Observability\ObservationKind;
use Kongono\Observability\ObservationRecorder;
use Kongono\Observability\ObservationStatus;
use Kongono\Observability\SignalIdentity;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class ObservationRecorderTest extends TestCase
{
    public function testRecorderIsExactSingleMethodCapabilityWithNoPackageImplementation(): void
    {
        $reflection = new ReflectionClass(ObservationRecorder::class);
        self::assertTrue($reflection->isInterface());
        self::assertSame(['record'], array_map(static fn($method): string => $method->getName(), $reflection->getMethods()));
        $method = $reflection->getMethod('record');
        self::assertSame('void', (string) $method->getReturnType());
        self::assertCount(1, $method->getParameters());
        self::assertSame(Observation::class, (string) $method->getParameters()[0]->getType());

        $observation = new Observation(
            ObservabilityContext::uncorrelated(),
            new SignalIdentity('test.signal', 1),
            ObservationKind::OperationalEvent,
            ObservationStatus::Ok,
            new AttributeSet([]),
        );
        $recorder = new class implements ObservationRecorder {
            public ?Observation $recorded = null;

            public function record(Observation $observation): void
            {
                $this->recorded = $observation;
            }
        };
        $recorder->record($observation);
        self::assertSame($observation, $recorder->recorded);

        $implementations = [];
        foreach (get_declared_classes() as $class) {
            $classReflection = new ReflectionClass($class);
            $fileName = $classReflection->getFileName();
            if (
                $classReflection->implementsInterface(ObservationRecorder::class)
                && is_string($fileName)
                && str_contains(str_replace('\\', '/', $fileName), '/packages/observability/src/')
            ) {
                $implementations[] = $class;
            }
        }
        self::assertSame([], $implementations);
    }
}
