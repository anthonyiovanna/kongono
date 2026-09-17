<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterContract\Tests\Unit\Adapter;

use Kongono\CacheAdapterContract\Adapter\CacheAdapterIdentity;
use Kongono\CacheAdapterContract\Exception\InvalidCacheAdapterContractException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CacheAdapterIdentityTest extends TestCase
{
    public function testCanonicalIdentityIsExplicitlyVersioned(): void
    {
        self::assertSame('bounded-memory@1', (new CacheAdapterIdentity('bounded-memory', 1))->canonical());
    }

    #[DataProvider('invalidIdentities')]
    public function testInvalidIdentityFailsClosed(string $name, int $version): void
    {
        $this->expectException(InvalidCacheAdapterContractException::class);
        new CacheAdapterIdentity($name, $version);
    }

    /** @return iterable<string, array{string, int}> */
    public static function invalidIdentities(): iterable
    {
        yield 'empty' => ['', 1];
        yield 'uppercase' => ['Memory', 1];
        yield 'version zero' => ['memory', 0];
        yield 'version too large' => ['memory', 65_536];
    }
}
