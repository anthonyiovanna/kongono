<?php

declare(strict_types=1);

namespace Kongono\CacheBuild\Tests\Unit\Profile;

use Kongono\CacheBuild\Exception\InvalidCacheProfileException;
use Kongono\CacheBuild\Profile\CacheProfileValueLimit;
use PHPUnit\Framework\TestCase;

final class CacheProfileValueLimitTest extends TestCase
{
    public function testFiniteBoundsAreExact(): void
    {
        self::assertSame(1, (new CacheProfileValueLimit(1))->bytes());
        self::assertSame(1_048_576, (new CacheProfileValueLimit(1_048_576))->bytes());
        self::assertTrue((new CacheProfileValueLimit(42))->equals(new CacheProfileValueLimit(42)));
    }

    public function testOutOfRangeValueFailsClosed(): void
    {
        $this->expectException(InvalidCacheProfileException::class);
        new CacheProfileValueLimit(1_048_577);
    }
}
