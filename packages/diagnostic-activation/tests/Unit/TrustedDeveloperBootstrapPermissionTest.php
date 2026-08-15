<?php

declare(strict_types=1);

namespace Kongono\DiagnosticActivation\Tests\Unit;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DiagnosticActivation\TrustedDeveloperBootstrapPermission;
use PHPUnit\Framework\TestCase;
use ReflectionEnum;

final class TrustedDeveloperBootstrapPermissionTest extends TestCase
{
    public function testCasesAndPermissionBehaviorAreExact(): void
    {
        self::assertSame(
            ['Denied', 'Allowed'],
            array_map(
                static fn(TrustedDeveloperBootstrapPermission $case): string => $case->name,
                TrustedDeveloperBootstrapPermission::cases(),
            ),
        );
        self::assertFalse(TrustedDeveloperBootstrapPermission::Denied->allowsTrustedPresentation());
        self::assertTrue(TrustedDeveloperBootstrapPermission::Allowed->allowsTrustedPresentation());
    }

    public function testDeclarationShapeAndExperimentalStatusAreExact(): void
    {
        $reflection = new ReflectionEnum(TrustedDeveloperBootstrapPermission::class);
        $attributes = $reflection->getAttributes(Api::class);
        $methodNames = array_map(
            static fn(\ReflectionMethod $method): string => $method->getName(),
            $reflection->getMethods(),
        );
        sort($methodNames);
        $permissionMethod = $reflection->getMethod('allowsTrustedPresentation');

        self::assertTrue($reflection->isEnum());
        self::assertNull($reflection->getBackingType());
        self::assertCount(1, $attributes);
        self::assertSame(ApiStatus::Experimental, $attributes[0]->newInstance()->status);
        self::assertSame(['allowsTrustedPresentation', 'cases'], $methodNames);
        self::assertSame('bool', (string) $permissionMethod->getReturnType());
        self::assertCount(0, $permissionMethod->getParameters());
        self::assertFalse($reflection->hasMethod('from'));
        self::assertFalse($reflection->hasMethod('tryFrom'));
    }
}
