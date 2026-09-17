<?php

declare(strict_types=1);

namespace Kongono\CacheContract\Tests\Unit\Version;

use Kongono\CacheContract\Exception\InvalidCacheContractException;
use Kongono\CacheContract\Identity\CacheContentIdentity;
use Kongono\CacheContract\Identity\CacheKeyIdentity;
use Kongono\CacheContract\Identity\CacheNamespace;
use Kongono\CacheContract\Identity\LogicalCacheKey;
use Kongono\CacheContract\Version\CacheEntryVersion;
use PHPUnit\Framework\TestCase;

final class CacheEntryVersionTest extends TestCase
{
    public function testTokenGrammarEqualityAndDomainSeparationAreExact(): void
    {
        $version = new CacheEntryVersion(str_repeat('a', 64));
        self::assertSame(str_repeat('a', 64), $version->token());
        self::assertTrue($version->equals(new CacheEntryVersion(str_repeat('a', 64))));
        self::assertFalse($version->equals(new CacheEntryVersion(str_repeat('b', 64))));
        self::assertNotSame(CacheKeyIdentity::from(new CacheNamespace('app'), new LogicalCacheKey('key'))->digest(), $version->token());
        self::assertNotSame(CacheContentIdentity::fromBytes('value')->digest(), $version->token());
        foreach (['', str_repeat('a', 63), str_repeat('a', 65), str_repeat('A', 64), str_repeat('g', 64)] as $invalid) {
            try {
                new CacheEntryVersion($invalid);
                self::fail('Expected entry-version grammar to fail.');
            } catch (InvalidCacheContractException) {
                self::addToAssertionCount(1);
            }
        }
    }
}
