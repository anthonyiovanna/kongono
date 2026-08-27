<?php

declare(strict_types=1);

namespace Kongono\IdentityAccess\Tests\Unit;

use Kongono\IdentityAccess\PrincipalId;
use Kongono\IdentityAccess\PrincipalKind;
use Kongono\IdentityAccess\PrincipalReference;
use Kongono\IdentityAccess\SecurityContext;
use Kongono\IdentityAccess\SecurityContextState;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class SecurityContextTest extends TestCase
{
    public function testAnonymousAndAuthenticatedShapesAreExact(): void
    {
        $anonymous = SecurityContext::anonymous();
        self::assertSame(SecurityContextState::Anonymous, $anonymous->state());
        self::assertNull($anonymous->principal());

        $principal = new PrincipalReference(PrincipalKind::Service, new PrincipalId('service-1'));
        $authenticated = SecurityContext::authenticated($principal);
        self::assertSame(SecurityContextState::Authenticated, $authenticated->state());
        self::assertSame($principal, $authenticated->principal());
        self::assertSame(['anonymous', 'authenticated'], array_column(SecurityContextState::cases(), 'value'));
    }

    public function testSecurityContextIsImmutableExplicitAndNonAmbient(): void
    {
        $reflection = new ReflectionClass(SecurityContext::class);

        self::assertTrue($reflection->isFinal());
        self::assertTrue($reflection->isReadOnly());
        self::assertTrue($reflection->getConstructor()?->isPrivate());
        self::assertSame([], $reflection->getStaticProperties());
        self::assertSame(['anonymous', 'authenticated', 'principal', 'state'], $this->declaredPublicMethods($reflection));
        foreach ($reflection->getProperties() as $property) {
            self::assertFalse($property->isStatic());
            self::assertTrue($property->isPrivate());
            self::assertTrue($property->isReadOnly());
        }
        foreach (['current', 'authorize', 'roles', 'permissions', 'tenant', 'session', 'credential', '__toString', '__serialize', '__debugInfo'] as $method) {
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
            if ($method->getDeclaringClass()->getName() === $reflection->getName() && $method->isPublic()) {
                $methods[] = $method->getName();
            }
        }
        sort($methods);

        return $methods;
    }
}
