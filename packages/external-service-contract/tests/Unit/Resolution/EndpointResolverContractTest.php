<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceContract\Tests\Unit\Resolution;

use Kongono\ExternalServiceContract\Resolution\EndpointResolver;
use Kongono\NetworkEgressRuntime\Resolver\EgressResolver;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class EndpointResolverContractTest extends TestCase
{
    public function testResolverExtendsExactPhase15PortWithoutWideningIt(): void
    {
        $reflection = new ReflectionClass(EndpointResolver::class);
        self::assertSame([EgressResolver::class], $reflection->getInterfaceNames());
        $declared = array_values(array_filter($reflection->getMethods(), static fn($method): bool => EndpointResolver::class === $method->getDeclaringClass()->getName()));
        self::assertSame([], $declared);
        $method = (new ReflectionClass(EgressResolver::class))->getMethod('resolve');
        self::assertSame('Kongono\\NetworkEgressContract\\Origin\\CanonicalOrigin', (string) $method->getParameters()[0]->getType());
        self::assertSame('Kongono\\NetworkEgressRuntime\\Resolver\\EgressResolutionResult', (string) $method->getReturnType());
    }
}
