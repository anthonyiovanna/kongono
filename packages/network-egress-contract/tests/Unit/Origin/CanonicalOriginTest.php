<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressContract\Tests\Unit\Origin;

use Kongono\Http\Message\Message\Uri;
use Kongono\NetworkEgressContract\Exception\InvalidNetworkEgressContractException;
use Kongono\NetworkEgressContract\Origin\CanonicalOrigin;
use Kongono\NetworkEgressContract\Origin\OriginScheme;
use PHPUnit\Framework\TestCase;

final class CanonicalOriginTest extends TestCase
{
    public function testCanonicalDnsIpv4Ipv6AndEffectivePortsAreExact(): void
    {
        $cases = [
            [OriginScheme::Http, 'example.test', 80],
            [OriginScheme::Https, 'a-b.example', 443],
            [OriginScheme::Https, '192.0.2.1', 8443],
            [OriginScheme::Https, '2001:db8::1', 443],
            [OriginScheme::Http, '::1', 8080],
        ];
        foreach ($cases as [$scheme, $host, $port]) {
            $origin = new CanonicalOrigin($scheme, $host, $port);
            self::assertSame($scheme, $origin->scheme());
            self::assertSame($host, $origin->host());
            self::assertSame($port, $origin->port());
            self::assertTrue($origin->equals(new CanonicalOrigin($scheme, $host, $port)));
        }

        self::assertTrue((new CanonicalOrigin(OriginScheme::Http, 'example.test', 80))->equals(CanonicalOrigin::fromUri(new Uri('http://example.test/path?query'))));
        self::assertTrue((new CanonicalOrigin(OriginScheme::Https, 'example.test', 443))->equals(CanonicalOrigin::fromUri(new Uri('https://example.test'))));
        self::assertTrue((new CanonicalOrigin(OriginScheme::Https, '2001:db8::1', 9443))->equals(CanonicalOrigin::fromUri(new Uri('https://[2001:db8::1]:9443/path'))));
    }

    public function testNoncanonicalAndSensitiveOriginInputsFailWithoutDisclosure(): void
    {
        $hosts = ['', 'EXAMPLE.test', 'example test', "example\ntest", 'tést.test', '-bad.test', 'bad-.test', '192.168.001.1', '256.1.1.1', '[::1]', 'fe80::1%zone', '2001:0db8::1', str_repeat('a', 254)];
        foreach ($hosts as $host) {
            try {
                new CanonicalOrigin(OriginScheme::Https, $host, 443);
                self::fail('Expected origin rejection.');
            } catch (InvalidNetworkEgressContractException $exception) {
                self::assertContains($exception->diagnosticId(), ['KGN-EGRESS-CONTRACT-1502', 'KGN-EGRESS-CONTRACT-1508']);
                if ('' !== $host) {
                    self::assertStringNotContainsString($host, (string) $exception);
                }
            }
        }
        foreach ([0, 65536] as $port) {
            try {
                new CanonicalOrigin(OriginScheme::Http, 'example.test', $port);
                self::fail('Expected port rejection.');
            } catch (InvalidNetworkEgressContractException $exception) {
                self::assertSame('KGN-EGRESS-CONTRACT-1502', $exception->diagnosticId());
            }
        }
        foreach ([new Uri('/relative'), new Uri('ftp://example.test'), new Uri('https://user:pass@example.test'), new Uri('https://example.test#fragment')] as $uri) {
            try {
                CanonicalOrigin::fromUri($uri);
                self::fail('Expected URI rejection.');
            } catch (InvalidNetworkEgressContractException $exception) {
                self::assertSame('KGN-EGRESS-CONTRACT-1502', $exception->diagnosticId());
                self::assertStringNotContainsString((string) $uri, (string) $exception);
            }
        }
    }
}
