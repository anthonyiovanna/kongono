<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressCurl\Tests\Unit\Profile;

use Kongono\NetworkEgressCurl\Profile\CurlTlsTrust;
use Kongono\NetworkEgressCurl\Profile\CurlTransportProfile;
use Kongono\NetworkEgressCurl\Tests\Fixture\FakeCurlExecutor;
use PHPUnit\Framework\TestCase;

final class CurlTransportProfileTest extends TestCase
{
    public function testSecureDefaultAndExplicitPlainHttpOptInAreImmutable(): void
    {
        $trust = new CurlTlsTrust(FakeCurlExecutor::pem());
        $secure = new CurlTransportProfile($trust);
        $plain = new CurlTransportProfile($trust, true);
        self::assertFalse($secure->allowsPlainHttp());
        self::assertTrue($plain->allowsPlainHttp());
        self::assertSame($trust, $secure->tlsTrust());
    }
}
