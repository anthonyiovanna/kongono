<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressCurl\Tests\Unit\Internal;

use Kongono\NetworkEgressCurl\Internal\CurlOptionBuilder;
use Kongono\NetworkEgressCurl\Internal\CurlRequestGuard;
use Kongono\NetworkEgressCurl\Internal\CurlResponseAccumulator;
use Kongono\NetworkEgressCurl\Tests\Fixture\FakeCurlExecutor;
use PHPUnit\Framework\TestCase;

final class CurlOptionBuilderTest extends TestCase
{
    public function testLockedSafetyOptionsAndIpv4BindingAreExact(): void
    {
        $request = FakeCurlExecutor::request();
        $profile = FakeCurlExecutor::profile();
        $reader = CurlRequestGuard::admit($request, $profile);
        $accumulator = new CurlResponseAccumulator($request->operation()->budget());
        $options = (new CurlOptionBuilder())->build($request, $profile, $reader, $accumulator);

        self::assertSame('https://example.test/resource', $options[CURLOPT_URL]);
        self::assertSame(['example.test:443:192.0.2.1'], $options[CURLOPT_RESOLVE]);
        self::assertSame(CURL_HTTP_VERSION_1_1, $options[CURLOPT_HTTP_VERSION]);
        self::assertSame('http,https', $options[CURLOPT_PROTOCOLS_STR]);
        self::assertSame('http,https', $options[CURLOPT_REDIR_PROTOCOLS_STR]);
        self::assertFalse($options[CURLOPT_FOLLOWLOCATION]);
        self::assertSame('', $options[CURLOPT_PROXY]);
        self::assertSame('*', $options[CURLOPT_NOPROXY]);
        self::assertSame(CURL_NETRC_IGNORED, $options[CURLOPT_NETRC]);
        self::assertTrue($options[CURLOPT_SSL_VERIFYPEER]);
        self::assertSame(2, $options[CURLOPT_SSL_VERIFYHOST]);
        self::assertSame(FakeCurlExecutor::pem(), $options[CURLOPT_CAINFO_BLOB]);
        self::assertFalse($options[CURLOPT_HTTP_CONTENT_DECODING]);
        self::assertTrue($options[CURLOPT_HTTP_TRANSFER_DECODING]);
        self::assertTrue($options[CURLOPT_FRESH_CONNECT]);
        self::assertTrue($options[CURLOPT_FORBID_REUSE]);
        $accumulator->close();
    }

    public function testIpv6BindingUsesOneBracketedExactAddress(): void
    {
        $request = FakeCurlExecutor::request(address: '2001:db8::1');
        $profile = FakeCurlExecutor::profile();
        $accumulator = new CurlResponseAccumulator($request->operation()->budget());
        $options = (new CurlOptionBuilder())->build($request, $profile, CurlRequestGuard::admit($request, $profile), $accumulator);
        self::assertSame(['example.test:443:[2001:db8::1]'], $options[CURLOPT_RESOLVE]);
        $accumulator->close();
    }
}
