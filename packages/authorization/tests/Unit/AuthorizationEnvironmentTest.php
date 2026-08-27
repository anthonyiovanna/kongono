<?php

declare(strict_types=1);

namespace Kongono\Authorization\Tests\Unit;

use Kongono\Authorization\AuthorizationEnvironmentEvidence;
use Kongono\Authorization\AuthorizationEnvironmentProfile;
use Kongono\Authorization\AuthorizationPolicyEvidence;
use Kongono\Authorization\Exception\InvalidAuthorizationValueException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class AuthorizationEnvironmentTest extends TestCase
{
    public function testProfileUsesExactCanonicalSyntaxAndBounds(): void
    {
        $profile = new AuthorizationEnvironmentProfile(str_repeat('a', 64));
        self::assertSame(str_repeat('a', 64), $profile->value());
        self::assertTrue((new AuthorizationEnvironmentProfile('facts.v1'))->equals(new AuthorizationEnvironmentProfile('facts.v1')));
        self::assertFalse((new AuthorizationEnvironmentProfile('facts.v1'))->equals(new AuthorizationEnvironmentProfile('facts.v2')));

        foreach (['', 'Facts', 'two words', str_repeat('a', 65), 'é'] as $invalid) {
            try {
                new AuthorizationEnvironmentProfile($invalid);
                self::fail('Invalid profile accepted.');
            } catch (InvalidAuthorizationValueException $failure) {
                self::assertSame('KGN-AUTHORIZATION-1001', $failure->diagnosticId());
                if ('' !== $invalid) {
                    self::assertStringNotContainsString($invalid, (string) $failure);
                }
            }
        }
    }

    public function testOpaqueEvidenceInterfacesExposeOnlyTheirIdentityMethods(): void
    {
        self::assertSame(['reference'], $this->declaredPublicMethods(AuthorizationPolicyEvidence::class));
        self::assertSame(['profile'], $this->declaredPublicMethods(AuthorizationEnvironmentEvidence::class));
    }

    /**
     * @param class-string $type
     * @return list<string>
     */
    private function declaredPublicMethods(string $type): array
    {
        $reflection = new ReflectionClass($type);
        $methods = [];
        foreach ($reflection->getMethods() as $method) {
            if ($method->getDeclaringClass()->getName() === $type && $method->isPublic()) {
                $methods[] = $method->getName();
            }
        }
        sort($methods);

        return $methods;
    }
}
