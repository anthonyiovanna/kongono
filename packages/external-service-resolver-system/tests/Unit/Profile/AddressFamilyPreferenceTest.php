<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceResolverSystem\Tests\Unit\Profile;

use Kongono\ExternalServiceResolverSystem\Profile\AddressFamilyPreference;
use PHPUnit\Framework\TestCase;

final class AddressFamilyPreferenceTest extends TestCase
{
    public function testClosedValuesAreExact(): void
    {
        self::assertSame(['ipv4_first', 'ipv6_first'], array_column(AddressFamilyPreference::cases(), 'value'));
    }
}
