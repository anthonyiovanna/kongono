<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceResolverSystem\Tests\Unit\Profile;

use Kongono\ExternalServiceResolverSystem\Exception\InvalidSystemResolverProfileException;
use Kongono\ExternalServiceResolverSystem\Profile\AddressFamilyPreference;
use Kongono\ExternalServiceResolverSystem\Profile\SystemResolverProfile;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SystemResolverProfileTest extends TestCase
{
    public function testBoundsPreferencesAndEquality(): void
    {
        foreach (AddressFamilyPreference::cases() as $preference) {
            foreach ([1, 64] as $limit) {
                $profile = new SystemResolverProfile($preference, $limit);
                self::assertSame($preference, $profile->addressFamilyPreference());
                self::assertSame($limit, $profile->maximumCandidates());
                self::assertTrue($profile->equals(new SystemResolverProfile($preference, $limit)));
            }
        }
    }

    #[DataProvider('invalidLimits')]
    public function testInvalidLimitsAreRejected(int $limit): void
    {
        $this->expectException(InvalidSystemResolverProfileException::class);
        new SystemResolverProfile(AddressFamilyPreference::Ipv4First, $limit);
    }

    /** @return iterable<array{int}> */
    public static function invalidLimits(): iterable
    {
        yield [0];
        yield [65];
    }
}
