<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceContract\Tests\Unit\Credential;

use Kongono\ExternalServiceContract\Credential\CredentialLease;
use Kongono\ExternalServiceContract\Credential\CredentialLeaseSource;
use Kongono\ExternalServiceContract\Credential\CredentialMaterialConsumer;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SensitiveParameter;

final class CredentialLeaseContractTest extends TestCase
{
    public function testInterfacesExposeOnlyExactMethods(): void
    {
        self::assertSame(['identity', 'reference', 'purpose', 'state', 'withMaterial', 'retire'], array_map(static fn($method): string => $method->getName(), (new ReflectionClass(CredentialLease::class))->getMethods()));
        self::assertSame(['acquire'], array_map(static fn($method): string => $method->getName(), (new ReflectionClass(CredentialLeaseSource::class))->getMethods()));
        self::assertSame(['consume'], array_map(static fn($method): string => $method->getName(), (new ReflectionClass(CredentialMaterialConsumer::class))->getMethods()));
    }

    public function testMaterialParameterIsTheSoleSensitiveBoundary(): void
    {
        $parameter = (new ReflectionClass(CredentialMaterialConsumer::class))->getMethod('consume')->getParameters()[0];
        self::assertSame('material', $parameter->getName());
        self::assertSame('string', (string) $parameter->getType());
        self::assertCount(1, $parameter->getAttributes(SensitiveParameter::class));
        self::assertSame('void', (string) (new ReflectionClass(CredentialLease::class))->getMethod('withMaterial')->getReturnType());
    }
}
