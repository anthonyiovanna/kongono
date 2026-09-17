<?php

declare(strict_types=1);

namespace Kongono\CacheContract\Tests\Unit\Identity;

use Kongono\CacheContract\Exception\InvalidCacheContractException;
use Kongono\CacheContract\Identity\CacheContentIdentity;
use Kongono\CacheContract\Identity\CacheKeyIdentity;
use Kongono\CacheContract\Identity\CacheNamespace;
use Kongono\CacheContract\Identity\LogicalCacheKey;
use PHPUnit\Framework\TestCase;

final class CacheIdentityTest extends TestCase
{
    public function testNamespaceAndKeyBoundsGrammarAndEqualityAreExact(): void
    {
        $namespace = new CacheNamespace('a' . str_repeat('1', 63));
        $key = new LogicalCacheKey('a' . str_repeat('1', 255));
        self::assertSame(64, strlen($namespace->value()));
        self::assertSame(256, strlen($key->value()));
        self::assertTrue($namespace->equals(new CacheNamespace($namespace->value())));
        self::assertTrue($key->equals(new LogicalCacheKey($key->value())));

        foreach (['', 'A', 'a b', "a\0b", 'a/b', 'é'] as $invalid) {
            $this->assertInvalid(static fn() => new CacheNamespace($invalid));
        }
        foreach (['', 'A', 'a b', "a\0b", 'a/b', 'é'] as $invalid) {
            $this->assertInvalid(static fn() => new LogicalCacheKey($invalid));
        }
        $this->assertInvalid(static fn() => new CacheNamespace('a' . str_repeat('1', 64)));
        $this->assertInvalid(static fn() => new LogicalCacheKey('a' . str_repeat('1', 256)));
        self::assertSame('a.b-c_d', (new CacheNamespace('a.b-c_d'))->value());
        self::assertSame('a:b.c-d_e', (new LogicalCacheKey('a:b.c-d_e'))->value());
    }

    public function testKeyAndContentIdentitiesRemainDistinctAndDomainSeparated(): void
    {
        $namespace = new CacheNamespace('app');
        $key = new LogicalCacheKey('user:1');
        $identity = CacheKeyIdentity::from($namespace, $key);
        self::assertSame(hash('sha256', "kongono.cache.key.v1\0app\0user:1"), $identity->digest());
        self::assertFalse($identity->equals(CacheKeyIdentity::from(new CacheNamespace('other'), $key)));
        self::assertFalse($identity->equals(CacheKeyIdentity::from($namespace, new LogicalCacheKey('user:2'))));
        self::assertSame(hash('sha256', 'bytes'), CacheContentIdentity::fromBytes('bytes')->digest());
        self::assertNotSame($identity->digest(), CacheContentIdentity::fromBytes('bytes')->digest());
        $this->assertInvalid(static fn() => new CacheKeyIdentity(str_repeat('A', 64)));
        $this->assertInvalid(static fn() => new CacheContentIdentity(str_repeat('g', 64)));
    }

    private function assertInvalid(callable $construct): void
    {
        try {
            $construct();
            self::fail('Expected bounded identity construction to fail.');
        } catch (InvalidCacheContractException) {
            self::addToAssertionCount(1);
        }
    }
}
