<?php

declare(strict_types=1);

namespace Kongono\AssetResolution\Tests\Unit\Browser;

use Kongono\AssetResolution\Browser\PublicAssetBaseUrl;
use Kongono\AssetResolution\Browser\PublicAssetUrl;
use Kongono\AssetResolution\Exception\InvalidAssetResolutionException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PublicAssetUrlTest extends TestCase
{
    public function testRootRelativeAndAbsoluteBasesAreExplicit(): void
    {
        $key = 'objects/sha256/aa/' . str_repeat('a', 64);
        self::assertSame('/assets/' . $key, (new PublicAssetUrl(new PublicAssetBaseUrl('/assets/'), $key))->value());
        self::assertSame('https://cdn.example.test/assets/' . $key, (new PublicAssetUrl(new PublicAssetBaseUrl('https://cdn.example.test/assets/'), $key))->value());
        self::assertSame('http://localhost:8080/assets/', (new PublicAssetBaseUrl('http://localhost:8080/assets/'))->value());
    }

    #[DataProvider('invalidBases')]
    public function testAmbiguousOrUnsafeBasesAreRejected(string $base): void
    {
        $this->expectException(InvalidAssetResolutionException::class);
        new PublicAssetBaseUrl($base);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidBases(): iterable
    {
        yield 'scheme-relative' => ['//cdn.example/assets/'];
        yield 'userinfo' => ['https://user@cdn.example/assets/'];
        yield 'query' => ['/assets/?v=1'];
        yield 'fragment' => ['/assets/#x'];
        yield 'backslash' => ['/assets\\x/'];
        yield 'dot' => ['/assets/../x/'];
        yield 'encoded separator' => ['/assets/%2F/x/'];
        yield 'ambiguous percent' => ['/assets/%25/x/'];
        yield 'non-ascii' => ['/asséts/'];
        yield 'control' => ["/assets/\n/"];
    }
}
