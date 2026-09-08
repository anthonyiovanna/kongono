<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressCurl\Tests\Unit\Profile;

use Kongono\NetworkEgressCurl\Exception\InvalidCurlTransportProfileException;
use Kongono\NetworkEgressCurl\Profile\CurlTlsTrust;
use Kongono\NetworkEgressCurl\Tests\Fixture\FakeCurlExecutor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use SensitiveParameter;

final class CurlTlsTrustTest extends TestCase
{
    public function testExactPemBytesAreCopiedAndConstructorIsSensitive(): void
    {
        $pem = FakeCurlExecutor::pem();
        $trust = new CurlTlsTrust($pem);
        $pem = 'changed';
        self::assertSame(FakeCurlExecutor::pem(), $trust->caBundlePem());
        $parameter = (new ReflectionMethod(CurlTlsTrust::class, '__construct'))->getParameters()[0];
        self::assertCount(1, $parameter->getAttributes(SensitiveParameter::class));
    }

    /** @return iterable<string, array{string}> */
    public static function invalidPem(): iterable
    {
        yield 'empty' => [''];
        yield 'nul' => ["-----BEGIN CERTIFICATE-----\0-----END CERTIFICATE-----"];
        yield 'incomplete' => ['-----BEGIN CERTIFICATE-----'];
        yield 'oversized' => [str_repeat('x', 1_048_577)];
    }

    #[DataProvider('invalidPem')]
    public function testInvalidPemFailsClosed(string $pem): void
    {
        $this->expectException(InvalidCurlTransportProfileException::class);
        new CurlTlsTrust($pem);
    }
}
