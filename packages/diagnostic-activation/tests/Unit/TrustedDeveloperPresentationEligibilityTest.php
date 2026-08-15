<?php

declare(strict_types=1);

namespace Kongono\DiagnosticActivation\Tests\Unit;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DiagnosticActivation\AdapterControlledDeliveryPermission;
use Kongono\DiagnosticActivation\TrustedDeveloperBootstrapPermission;
use Kongono\DiagnosticActivation\TrustedDeveloperPresentationEligibility;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class TrustedDeveloperPresentationEligibilityTest extends TestCase
{
    public function testEveryPermissionCombinationProducesTheExactTruthTable(): void
    {
        self::assertFalse((new TrustedDeveloperPresentationEligibility(
            TrustedDeveloperBootstrapPermission::Denied,
            AdapterControlledDeliveryPermission::Denied,
        ))->allowsTrustedPresentation());
        self::assertFalse((new TrustedDeveloperPresentationEligibility(
            TrustedDeveloperBootstrapPermission::Denied,
            AdapterControlledDeliveryPermission::Allowed,
        ))->allowsTrustedPresentation());
        self::assertFalse((new TrustedDeveloperPresentationEligibility(
            TrustedDeveloperBootstrapPermission::Allowed,
            AdapterControlledDeliveryPermission::Denied,
        ))->allowsTrustedPresentation());
        self::assertTrue((new TrustedDeveloperPresentationEligibility(
            TrustedDeveloperBootstrapPermission::Allowed,
            AdapterControlledDeliveryPermission::Allowed,
        ))->allowsTrustedPresentation());
    }

    public function testDeclarationShapeAndExperimentalStatusAreExact(): void
    {
        $reflection = new ReflectionClass(TrustedDeveloperPresentationEligibility::class);
        $attributes = $reflection->getAttributes(Api::class);
        $properties = $reflection->getProperties();
        $methods = array_values(array_filter(
            $reflection->getMethods(),
            static fn(\ReflectionMethod $method): bool => $reflection->getName() === $method->getDeclaringClass()->getName()
                && $method->isPublic(),
        ));
        $constructor = $reflection->getConstructor();
        $permissionMethod = $reflection->getMethod('allowsTrustedPresentation');

        self::assertTrue($reflection->isFinal());
        self::assertTrue($reflection->isReadOnly());
        self::assertCount(1, $attributes);
        self::assertSame(ApiStatus::Experimental, $attributes[0]->newInstance()->status);
        self::assertSame(['bootstrapPermission', 'deliveryPermission'], array_map(
            static fn(\ReflectionProperty $property): string => $property->getName(),
            $properties,
        ));
        self::assertSame([
            TrustedDeveloperBootstrapPermission::class,
            AdapterControlledDeliveryPermission::class,
        ], array_map(
            static fn(\ReflectionProperty $property): string => (string) $property->getType(),
            $properties,
        ));

        foreach ($properties as $property) {
            self::assertTrue($property->isPrivate());
            self::assertTrue($property->isReadOnly());
            self::assertFalse($property->isStatic());
        }

        self::assertSame(['__construct', 'allowsTrustedPresentation'], array_map(
            static fn(\ReflectionMethod $method): string => $method->getName(),
            $methods,
        ));
        self::assertSame([
            TrustedDeveloperBootstrapPermission::class,
            AdapterControlledDeliveryPermission::class,
        ], array_map(
            static fn(\ReflectionParameter $parameter): string => (string) $parameter->getType(),
            $constructor?->getParameters() ?? [],
        ));
        self::assertSame('bool', (string) $permissionMethod->getReturnType());
        self::assertCount(0, $permissionMethod->getParameters());
    }
}
