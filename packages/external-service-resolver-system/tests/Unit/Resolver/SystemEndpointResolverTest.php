<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceResolverSystem\Tests\Unit\Resolver;

use Kongono\ExternalServiceContract\Resolution\EndpointResolver;
use Kongono\ExternalServiceResolverSystem\Profile\AddressFamilyPreference;
use Kongono\ExternalServiceResolverSystem\Profile\SystemResolverProfile;
use Kongono\ExternalServiceResolverSystem\Resolver\SystemEndpointResolver;
use Kongono\NetworkEgressContract\Origin\CanonicalOrigin;
use Kongono\NetworkEgressContract\Origin\OriginScheme;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class SystemEndpointResolverTest extends TestCase
{
    #[DataProvider('loopbacks')]
    public function testNativeNumericLoopbacksResolveWithoutDns(string $host, AddressFamilyPreference $preference): void
    {
        $resolver = new SystemEndpointResolver(new SystemResolverProfile($preference, 8));
        self::assertInstanceOf(EndpointResolver::class, $resolver);
        $result = $resolver->resolve(new CanonicalOrigin(OriginScheme::Https, $host, 443));
        self::assertTrue($result->succeeded());
        self::assertSame($host, $result->evidence()?->selectedAddress()->value());
        self::assertCount(1, (new ReflectionClass($resolver))->getProperties());
    }

    /** @return iterable<array{string, AddressFamilyPreference}> */
    public static function loopbacks(): iterable
    {
        yield ['127.0.0.1', AddressFamilyPreference::Ipv4First];
        yield ['::1', AddressFamilyPreference::Ipv6First];
    }
}
