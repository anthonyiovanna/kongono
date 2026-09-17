<?php

declare(strict_types=1);

namespace Kongono\CacheContract\Tests\Unit\Codec;

use Kongono\CacheContract\Codec\CacheCodecIdentity;
use Kongono\CacheContract\Exception\InvalidCacheContractException;
use PHPUnit\Framework\TestCase;

final class CacheCodecIdentityTest extends TestCase
{
    public function testNameVersionCanonicalFormBoundsAndEqualityAreExact(): void
    {
        $codec = new CacheCodecIdentity('a' . str_repeat('1', 95), 65_535);
        self::assertSame(96, strlen($codec->name()));
        self::assertSame(65_535, $codec->version());
        self::assertSame($codec->name() . '@65535', $codec->canonical());
        self::assertTrue($codec->equals(new CacheCodecIdentity($codec->name(), 65_535)));
        self::assertFalse($codec->equals(new CacheCodecIdentity($codec->name(), 1)));

        foreach ([['', 1], ['Upper', 1], ['a/b', 1], ['a', 0], ['a', -1], ['a', 65_536], ['a' . str_repeat('1', 96), 1]] as [$name, $version]) {
            try {
                new CacheCodecIdentity($name, $version);
                self::fail('Expected codec identity construction to fail.');
            } catch (InvalidCacheContractException) {
                self::addToAssertionCount(1);
            }
        }
    }
}
