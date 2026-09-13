<?php

declare(strict_types=1);

namespace Kongono\ViewHttp\Tests\Integration;

use Kongono\ViewHttp\Policy\ViewResponseHeader;
use Kongono\ViewHttp\Tests\Fixture\ViewHttpFixture;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CompletedViewResponseAdapterIntegrationTest extends TestCase
{
    #[DataProvider('outputs')]
    public function testKongonoFactoriesRoundTripExactBytes(string $bytes): void
    {
        $response = ViewHttpFixture::adapter()->adapt(ViewHttpFixture::result($bytes), ViewHttpFixture::policy(200, '', [new ViewResponseHeader('X-Policy', ['explicit'])]));
        self::assertSame($bytes, (string) $response->getBody());
        self::assertSame(strlen($bytes), $response->getBody()->getSize());
        self::assertSame((string) strlen($bytes), $response->getHeaderLine('Content-Length'));
        self::assertSame(['explicit'], $response->getHeader('X-Policy'));
        self::assertCount(3, $response->getHeaders());
    }

    /** @return iterable<string, array{string}> */
    public static function outputs(): iterable
    {
        yield 'empty' => [''];
        yield 'ascii' => ['hello'];
        yield 'markup' => ['<main data-id="1">safe</main>'];
        yield 'horizontal tab' => ["a\tb"];
        yield 'utf8' => ['κόσμος'];
        yield 'large' => [str_repeat('x', 8192)];
    }

    public function testResponseBodyIsRewoundAtReturn(): void
    {
        $response = ViewHttpFixture::adapter()->adapt(ViewHttpFixture::result('rewound'), ViewHttpFixture::policy());
        self::assertSame(0, $response->getBody()->tell());
        self::assertSame('rewound', $response->getBody()->read(7));
        self::assertSame(7, $response->getBody()->tell());
    }

    public function testCallerHeadersRetainOrderAndSpelling(): void
    {
        $response = ViewHttpFixture::adapter()->adapt(ViewHttpFixture::result(), ViewHttpFixture::policy(headers: [new ViewResponseHeader('X-Zed', ['z']), new ViewResponseHeader('X-Alpha', ['a'])]));
        self::assertSame(['Content-Type', 'Content-Length', 'X-Zed', 'X-Alpha'], array_keys($response->getHeaders()));
        self::assertSame(['z'], $response->getHeader('X-Zed'));
        self::assertSame(['a'], $response->getHeader('X-Alpha'));
    }

    #[DataProvider('statusPolicies')]
    public function testEveryCompletedResponseStatusClassIsCallerSelected(int $status, string $reason): void
    {
        $response = ViewHttpFixture::adapter()->adapt(ViewHttpFixture::result('status-body'), ViewHttpFixture::policy($status, $reason));
        self::assertSame($status, $response->getStatusCode());
        self::assertSame($reason, $response->getReasonPhrase());
        self::assertSame('status-body', (string) $response->getBody());
        self::assertArrayNotHasKey('Location', $response->getHeaders());
        self::assertArrayNotHasKey('Cache-Control', $response->getHeaders());
        self::assertArrayNotHasKey('Content-Security-Policy', $response->getHeaders());
        self::assertArrayNotHasKey('Set-Cookie', $response->getHeaders());
    }

    /** @return iterable<string, array{int, string}> */
    public static function statusPolicies(): iterable
    {
        yield 'success' => [200, 'OK'];
        yield 'redirect' => [302, 'Found'];
        yield 'client error' => [404, 'Not Found'];
        yield 'server error' => [503, 'Service Unavailable'];
    }

    public function testMaximumBoundedCompletedOutputIsExact(): void
    {
        $bytes = str_repeat('x', 8388608);
        $response = ViewHttpFixture::adapter()->adapt(ViewHttpFixture::result($bytes), ViewHttpFixture::policy());
        self::assertSame(8388608, $response->getBody()->getSize());
        self::assertSame('8388608', $response->getHeaderLine('Content-Length'));
        self::assertSame(hash('sha256', $bytes), hash('sha256', (string) $response->getBody()));
        self::assertSame($bytes, (string) $response->getBody());
    }
}
