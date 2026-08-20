<?php

declare(strict_types=1);

namespace Kongono\Http\Message\Tests\Unit\Message;

use Kongono\Http\Message\Exception\InvalidMessageArgumentException;
use Kongono\Http\Message\Message\Uri;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UriTest extends TestCase
{
    /** @return iterable<string, array{string, string}> */
    public static function uriCases(): iterable
    {
        yield 'empty' => ['', ''];
        yield 'relative' => ['a/b', 'a/b'];
        yield 'absolute' => ['HTTP://Example.COM:80/a?b#c', 'http://example.com/a?b#c'];
        yield 'authority' => ['//Example.com:8080', '//example.com:8080'];
        yield 'query only' => ['?a=b', '?a=b'];
        yield 'fragment only' => ['#part', '#part'];
        yield 'ipv4' => ['http://127.0.0.1/', 'http://127.0.0.1/'];
        yield 'ipv6' => ['http://[2001:DB8::1]/', 'http://[2001:db8::1]/'];
        yield 'encoded' => ['/a%20b/é?q=é', '/a%20b/%C3%A9?q=%C3%A9'];
    }

    #[DataProvider('uriCases')]
    public function testParsingAndSerializationAreDeterministic(string $input, string $expected): void
    {
        self::assertSame($expected, (string) new Uri($input));
    }

    public function testComponentCopiesAlwaysReturnDistinctValues(): void
    {
        $uri = new Uri('https://user:pass@example.com/path?q#f');
        foreach ([$uri->withScheme('https'), $uri->withUserInfo('user', 'pass'), $uri->withHost('example.com'), $uri->withPort(null), $uri->withPath('/path'), $uri->withQuery('?q'), $uri->withFragment('#f')] as $copy) {
            self::assertNotSame($uri, $copy);
        }
        self::assertSame('user:pass', $uri->getUserInfo());
        self::assertSame('user:pass@example.com', $uri->getAuthority());
        self::assertSame('https://user:pass@example.com/path?q#f', (string) $uri);
    }

    public function testPortsEncodingAndPathAssemblyBoundaries(): void
    {
        self::assertNull((new Uri('https://a:443'))->getPort());
        self::assertNull((new Uri('http://a:80'))->getPort());
        self::assertSame(81, (new Uri('http://a:81'))->withScheme('https')->getPort());
        self::assertSame('//host/rootless', (string) (new Uri())->withHost('host')->withPath('rootless'));
        self::assertSame('/many', (string) (new Uri())->withPath('///many'));
        self::assertSame('/%25', (string) (new Uri())->withPath('/%25'));
    }

    public function testExplicitDefaultPortsAreReevaluatedAfterSchemeChanges(): void
    {
        $http = new Uri('http://example.test:80/path');
        self::assertNull($http->getPort());
        self::assertSame('http://example.test/path', (string) $http);
        $https = $http->withScheme('https');
        self::assertSame(80, $https->getPort());
        self::assertSame('https://example.test:80/path', (string) $https);

        $httpsDefault = new Uri('https://example.test:443/path');
        self::assertNull($httpsDefault->getPort());
        self::assertSame(443, $httpsDefault->withScheme('http')->getPort());
        self::assertSame('http://example.test:443/path', (string) $httpsDefault->withScheme('http'));
        self::assertNull((new Uri('http://example.test'))->withScheme('https')->getPort());
    }

    public function testEmptyUserRetainsEveryNonNullPassword(): void
    {
        $uri = new Uri('https://example.test/path');
        self::assertSame('', $uri->withUserInfo('')->getUserInfo());
        self::assertSame('', $uri->withUserInfo('', null)->getUserInfo());
        self::assertSame(':', $uri->withUserInfo('', '')->getUserInfo());
        self::assertSame(':@example.test', $uri->withUserInfo('', '')->getAuthority());
        self::assertSame(':secret', $uri->withUserInfo('', 'secret')->getUserInfo());
        self::assertSame(':%2F%20', $uri->withUserInfo('', '/ ')->getUserInfo());
        self::assertSame('https://:%2F%20@example.test/path', (string) $uri->withUserInfo('', '/ '));
    }

    public function testInvalidSyntaxComponentsAndPortsFailWithoutRawDisclosure(): void
    {
        foreach (['http://é/', 'http://bad host/', 'http://a:0', 'http://a:65536', '/bad%2'] as $input) {
            try {
                new Uri($input);
                self::fail('Invalid URI must fail.');
            } catch (InvalidMessageArgumentException $exception) {
                self::assertStringNotContainsString($input, $exception->getMessage());
                self::assertStringStartsWith('KGN-HTTP-', $exception->diagnosticId());
            }
        }
    }

    public function testHostSchemeAndPortBoundariesAreExact(): void
    {
        self::assertSame('reg-name.example', (new Uri())->withHost('REG-NAME.Example')->getHost());
        self::assertSame('127.0.0.1', (new Uri())->withHost('127.0.0.1')->getHost());
        self::assertSame('[2001:db8::1]', (new Uri())->withHost('[2001:DB8::1]')->getHost());
        self::assertSame(1, (new Uri())->withPort(1)->getPort());
        self::assertSame(65535, (new Uri())->withPort(65535)->getPort());
        self::assertSame('custom+scheme', (new Uri())->withScheme('CUSTOM+SCHEME')->getScheme());

        foreach (['1http', 'bad scheme', 'http:'] as $scheme) {
            try {
                (new Uri())->withScheme($scheme);
                self::fail('Invalid schemes must fail.');
            } catch (InvalidMessageArgumentException $exception) {
                self::assertSame('KGN-HTTP-1011', $exception->diagnosticId());
            }
        }
        foreach (['é.example', 'bad host', '[not-an-ip]', 'a:b'] as $host) {
            try {
                (new Uri())->withHost($host);
                self::fail('Invalid hosts must fail.');
            } catch (InvalidMessageArgumentException $exception) {
                self::assertSame('KGN-HTTP-1011', $exception->diagnosticId());
            }
        }
        foreach ([0, 65536] as $port) {
            try {
                (new Uri())->withPort($port);
                self::fail('Out-of-range ports must fail.');
            } catch (InvalidMessageArgumentException $exception) {
                self::assertSame('KGN-HTTP-1012', $exception->diagnosticId());
            }
        }
    }

    public function testComponentEncodingPreservesTripletsAndEncodesUnicodeBytes(): void
    {
        $uri = (new Uri())
            ->withScheme('https')
            ->withUserInfo('u ser', 'p/é')
            ->withHost('example.test')
            ->withPort(8443)
            ->withPath('root/%2f/é')
            ->withQuery('?a=%2f&b=é')
            ->withFragment('#f/%2f/é');
        self::assertSame('u%20ser:p%2F%C3%A9', $uri->getUserInfo());
        self::assertSame('root/%2f/%C3%A9', $uri->getPath());
        self::assertSame('a=%2f&b=%C3%A9', $uri->getQuery());
        self::assertSame('f/%2f/%C3%A9', $uri->getFragment());
        self::assertSame('https://u%20ser:p%2F%C3%A9@example.test:8443/root/%2f/%C3%A9?a=%2f&b=%C3%A9#f/%2f/%C3%A9', (string) $uri);
    }

    public function testMalformedPercentTripletsFailInEveryComponent(): void
    {
        $operations = [
            static fn(): \Psr\Http\Message\UriInterface => (new Uri())->withUserInfo('%'),
            static fn(): \Psr\Http\Message\UriInterface => (new Uri())->withHost('bad%2'),
            static fn(): \Psr\Http\Message\UriInterface => (new Uri())->withPath('/bad%2'),
            static fn(): \Psr\Http\Message\UriInterface => (new Uri())->withQuery('bad%GG'),
            static fn(): \Psr\Http\Message\UriInterface => (new Uri())->withFragment('bad%0'),
        ];
        foreach ($operations as $operation) {
            try {
                $operation();
                self::fail('Malformed percent triplets must fail.');
            } catch (InvalidMessageArgumentException $exception) {
                self::assertSame('KGN-HTTP-1011', $exception->diagnosticId());
            }
        }
    }
}
