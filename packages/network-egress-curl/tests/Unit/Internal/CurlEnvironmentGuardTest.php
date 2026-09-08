<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressCurl\Tests\Unit\Internal;

use Kongono\NetworkEgressCurl\Internal\CurlEnvironmentGuard;
use PHPUnit\Framework\TestCase;

final class CurlEnvironmentGuardTest extends TestCase
{
    public function testCurrentNativeEnvironmentSuppliesEveryLockedCapability(): void
    {
        CurlEnvironmentGuard::assertSupported();
        self::assertTrue(extension_loaded('curl'));
        self::assertNotSame('', curl_version()['ssl_version'] ?? '');
        foreach (['CURLOPT_CAINFO_BLOB', 'CURLOPT_PROTOCOLS_STR', 'CURLOPT_REDIR_PROTOCOLS_STR', 'CURLOPT_RESOLVE', 'CURLOPT_XFERINFOFUNCTION', 'CURLOPT_HTTP_CONTENT_DECODING', 'CURLOPT_HTTP_TRANSFER_DECODING', 'CURL_HTTP_VERSION_1_1', 'CURL_NETRC_IGNORED'] as $constant) {
            self::assertTrue(defined($constant));
        }
    }
}
