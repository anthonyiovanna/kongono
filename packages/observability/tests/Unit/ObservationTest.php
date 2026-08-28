<?php

declare(strict_types=1);

namespace Kongono\Observability\Tests\Unit;

use Kongono\Observability\AttributeSet;
use Kongono\Observability\ObservabilityContext;
use Kongono\Observability\Observation;
use Kongono\Observability\ObservationKind;
use Kongono\Observability\ObservationStatus;
use Kongono\Observability\SignalIdentity;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class ObservationTest extends TestCase
{
    public function testClosedKindsStatusesAndExactEnvelopeAreInert(): void
    {
        self::assertSame(['trace', 'metric', 'operational_event', 'security_audit'], array_column(ObservationKind::cases(), 'value'));
        self::assertSame(['unset', 'ok', 'error'], array_column(ObservationStatus::cases(), 'value'));

        $context = ObservabilityContext::uncorrelated();
        $identity = new SignalIdentity('operation.completed', 1);
        $attributes = new AttributeSet([]);
        $observation = new Observation($context, $identity, ObservationKind::SecurityAudit, ObservationStatus::Error, $attributes);
        self::assertSame($context, $observation->context());
        self::assertSame($identity, $observation->identity());
        self::assertSame(ObservationKind::SecurityAudit, $observation->kind());
        self::assertSame(ObservationStatus::Error, $observation->status());
        self::assertSame($attributes, $observation->attributes());

        $reflection = new ReflectionClass($observation);
        self::assertTrue($reflection->isFinal());
        self::assertTrue($reflection->isReadOnly());
        self::assertSame(['__construct', 'attributes', 'context', 'identity', 'kind', 'status'], $this->declaredPublicMethods($reflection));
        foreach (['timestamp', 'duration', 'parent', 'sampled', 'payload', 'message', 'severity', 'count', 'measurement', 'export'] as $method) {
            self::assertFalse($reflection->hasMethod($method));
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
