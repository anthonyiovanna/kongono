<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceResolverSystem\Tests\Unit\Internal;

use Error;
use Kongono\ExternalServiceResolverSystem\Internal\AddressInfoLookupFailure;
use Kongono\ExternalServiceResolverSystem\Internal\AddressInfoLookupResult;
use Kongono\ExternalServiceResolverSystem\Internal\AddressInfoRecord;
use Kongono\ExternalServiceResolverSystem\Internal\SystemResolverEngine;
use Kongono\ExternalServiceResolverSystem\Profile\AddressFamilyPreference;
use Kongono\ExternalServiceResolverSystem\Profile\SystemResolverProfile;
use Kongono\ExternalServiceResolverSystem\Tests\Fixture\FakeAddressInfoLookup;
use Kongono\NetworkEgressContract\Origin\CanonicalOrigin;
use Kongono\NetworkEgressContract\Origin\OriginScheme;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SystemResolverEngineTest extends TestCase
{
    public function testOneHostnameLookupUsesExactThreeHintsAndNoFamilyKey(): void
    {
        $lookup = new FakeAddressInfoLookup([AddressInfoLookupResult::succeeded([self::v4('8.8.8.8', 8443)])]);
        $result = (new SystemResolverEngine($lookup))->resolve(
            new CanonicalOrigin(OriginScheme::Https, 'example.com', 8443),
            new SystemResolverProfile(AddressFamilyPreference::Ipv4First, 7),
        );
        self::assertTrue($result->succeeded());
        self::assertSame(1, $lookup->calls);
        self::assertSame('example.com', $lookup->host);
        self::assertSame('8443', $lookup->service);
        self::assertSame(['ai_socktype' => SOCK_STREAM, 'ai_protocol' => SOL_TCP, 'ai_flags' => AI_NUMERICSERV], $lookup->hints);
        self::assertArrayNotHasKey('ai_family', $lookup->hints ?? []);
        self::assertSame(7, $lookup->maximumCandidates);
    }

    public function testNumericHostAddsOnlyNumericHostFlag(): void
    {
        $lookup = new FakeAddressInfoLookup([AddressInfoLookupResult::succeeded([self::v4('127.0.0.1')])]);
        (new SystemResolverEngine($lookup))->resolve(
            new CanonicalOrigin(OriginScheme::Http, '127.0.0.1', 80),
            new SystemResolverProfile(AddressFamilyPreference::Ipv6First, 1),
        );
        self::assertSame(AI_NUMERICSERV | AI_NUMERICHOST, $lookup->hints['ai_flags'] ?? null);
    }

    #[DataProvider('failures')]
    public function testClosedLookupFailuresMapWithoutRetry(AddressInfoLookupFailure $failure): void
    {
        $lookup = new FakeAddressInfoLookup([AddressInfoLookupResult::failed($failure)]);
        $result = (new SystemResolverEngine($lookup))->resolve(
            new CanonicalOrigin(OriginScheme::Https, 'example.com', 443),
            new SystemResolverProfile(AddressFamilyPreference::Ipv4First, 4),
        );
        self::assertFalse($result->succeeded());
        self::assertSame($failure->value, $result->failure()?->reason());
        self::assertSame(1, $lookup->calls);
    }

    /** @return iterable<array{AddressInfoLookupFailure}> */
    public static function failures(): iterable
    {
        foreach (AddressInfoLookupFailure::cases() as $failure) {
            yield [$failure];
        }
    }

    public function testSequentialCallsAreFreshAndChangedAnswersRemainVisible(): void
    {
        $lookup = new FakeAddressInfoLookup([
            AddressInfoLookupResult::succeeded([self::v4('1.1.1.1')]),
            AddressInfoLookupResult::succeeded([self::v4('8.8.8.8')]),
        ]);
        $engine = new SystemResolverEngine($lookup);
        $origin = new CanonicalOrigin(OriginScheme::Https, 'example.com', 443);
        $profile = new SystemResolverProfile(AddressFamilyPreference::Ipv4First, 4);
        $firstEvidence = $engine->resolve($origin, $profile)->evidence();
        self::assertNotNull($firstEvidence);
        self::assertSame('1.1.1.1', $firstEvidence->selectedAddress()->value());
        $secondEvidence = $engine->resolve($origin, $profile)->evidence();
        self::assertNotNull($secondEvidence);
        self::assertSame('8.8.8.8', $secondEvidence->selectedAddress()->value());
        self::assertSame(2, $lookup->calls);
    }

    public function testErrorIdentityPropagatesUnchanged(): void
    {
        $error = new Error('native internal violation');
        $lookup = new FakeAddressInfoLookup([$error]);
        try {
            (new SystemResolverEngine($lookup))->resolve(
                new CanonicalOrigin(OriginScheme::Https, 'example.com', 443),
                new SystemResolverProfile(AddressFamilyPreference::Ipv4First, 4),
            );
            self::fail('Expected Error propagation.');
        } catch (Error $caught) {
            self::assertSame($error, $caught);
        }
    }

    private static function v4(string $value, int $port = 443): AddressInfoRecord
    {
        return new AddressInfoRecord(['ai_family' => AF_INET, 'ai_socktype' => SOCK_STREAM, 'ai_protocol' => SOL_TCP, 'ai_addr' => ['sin_addr' => $value, 'sin_port' => $port]]);
    }
}
