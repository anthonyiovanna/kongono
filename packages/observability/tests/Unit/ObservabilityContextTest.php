<?php

declare(strict_types=1);

namespace Kongono\Observability\Tests\Unit;

use Kongono\Observability\CorrelationId;
use Kongono\Observability\ObservabilityContext;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class ObservabilityContextTest extends TestCase
{
    public function testExplicitShapesFreshInstancesAndObjectIdentityAreExact(): void
    {
        $first = ObservabilityContext::uncorrelated();
        $second = ObservabilityContext::uncorrelated();
        self::assertNotSame($first, $second);
        self::assertNull($first->correlationId());
        self::assertFalse($first->isCorrelated());

        $correlationId = new CorrelationId('operation-42');
        $correlated = ObservabilityContext::correlated($correlationId);
        self::assertSame($correlationId, $correlated->correlationId());
        self::assertTrue($correlated->isCorrelated());
    }

    public function testContextIsFinalReadonlyPrivateAndNonAmbient(): void
    {
        $reflection = new ReflectionClass(ObservabilityContext::class);
        self::assertTrue($reflection->isFinal());
        self::assertTrue($reflection->isReadOnly());
        self::assertTrue($reflection->getConstructor()?->isPrivate());
        self::assertSame([], $reflection->getStaticProperties());
        self::assertSame(['correlated', 'correlationId', 'isCorrelated', 'uncorrelated'], $this->declaredPublicMethods($reflection));
        foreach (['current', 'traceId', 'spanId', 'attributes', 'sampled', '__toString', '__serialize', '__debugInfo'] as $method) {
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
