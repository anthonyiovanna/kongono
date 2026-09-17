<?php

declare(strict_types=1);

namespace Kongono\CacheBuild\Tests\Unit\Profile;

use Kongono\CacheBuild\Profile\CacheProfileDeclaration;
use Kongono\CacheBuild\Profile\CacheProfileName;
use Kongono\CacheBuild\Profile\CacheProfileValueLimit;
use Kongono\CacheContract\Codec\CacheCodecIdentity;
use Kongono\CacheContract\ContractVersion;
use Kongono\CacheContract\Freshness\CacheLifetime;
use Kongono\CacheContract\Identity\CacheNamespace;
use PHPUnit\Framework\TestCase;

final class CacheProfileDeclarationTest extends TestCase
{
    public function testDeclarationKeepsDomainsDistinct(): void
    {
        $profile = new CacheProfileDeclaration(ContractVersion::Version1, new CacheProfileName('articles'), new CacheNamespace('article-cache'), new CacheCodecIdentity('json', 2), new CacheLifetime(5_000_000), new CacheProfileValueLimit(4096));
        self::assertSame('articles', $profile->name()->value());
        self::assertSame('article-cache', $profile->namespace()->value());
        self::assertSame('json@2', $profile->codec()->canonical());
        self::assertSame(5_000_000, $profile->lifetime()->microseconds());
        self::assertSame(4096, $profile->valueLimit()->bytes());
        self::assertTrue($profile->equals($profile));
    }
}
