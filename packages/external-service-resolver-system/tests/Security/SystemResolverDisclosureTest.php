<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceResolverSystem\Tests\Security;

use Kongono\ExternalServiceResolverSystem\Exception\InvalidSystemResolverProfileException;
use Kongono\ExternalServiceResolverSystem\Internal\AddressInfoLookupFailure;
use Kongono\ExternalServiceResolverSystem\Internal\AddressInfoLookupResult;
use Kongono\ExternalServiceResolverSystem\Internal\SystemResolverEngine;
use Kongono\ExternalServiceResolverSystem\Profile\AddressFamilyPreference;
use Kongono\ExternalServiceResolverSystem\Profile\SystemResolverProfile;
use Kongono\ExternalServiceResolverSystem\Tests\Fixture\FakeAddressInfoLookup;
use Kongono\NetworkEgressContract\Origin\CanonicalOrigin;
use Kongono\NetworkEgressContract\Origin\OriginScheme;
use PHPUnit\Framework\TestCase;

final class SystemResolverDisclosureTest extends TestCase
{
    public function testFailuresAndExceptionsDoNotDiscloseResolverInputs(): void
    {
        $secret = 'secret-host.example';
        $lookup = new FakeAddressInfoLookup([AddressInfoLookupResult::failed(AddressInfoLookupFailure::LookupFailed)]);
        $result = (new SystemResolverEngine($lookup))->resolve(
            new CanonicalOrigin(OriginScheme::Https, $secret, 443),
            new SystemResolverProfile(AddressFamilyPreference::Ipv4First, 8),
        );
        self::assertFalse($result->succeeded());
        self::assertSame('system_resolver_lookup_failed', $result->failure()?->reason());
        self::assertStringNotContainsString($secret, serialize($result));

        try {
            new SystemResolverProfile(AddressFamilyPreference::Ipv4First, -123456);
            self::fail('Expected an invalid profile.');
        } catch (InvalidSystemResolverProfileException $exception) {
            self::assertStringNotContainsString('-123456', (string) $exception);
            self::assertStringNotContainsString('-123456', $exception->getMessage());
        }
    }
}
