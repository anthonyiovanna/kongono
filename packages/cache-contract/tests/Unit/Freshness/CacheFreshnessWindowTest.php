<?php

declare(strict_types=1);

namespace Kongono\CacheContract\Tests\Unit\Freshness;

use Kongono\CacheContract\Exception\InvalidCacheContractException;
use Kongono\CacheContract\Freshness\CacheFreshness;
use Kongono\CacheContract\Freshness\CacheFreshnessWindow;
use Kongono\CacheContract\Freshness\CacheLifetime;
use Kongono\CacheContract\Time\CacheInstant;
use PHPUnit\Framework\TestCase;

final class CacheFreshnessWindowTest extends TestCase
{
    public function testFiniteLifetimeBoundsAndExpiryBoundaryAreExact(): void
    {
        self::assertSame(1, (new CacheLifetime(1))->microseconds());
        self::assertSame(31_536_000_000_000, (new CacheLifetime(31_536_000_000_000))->microseconds());
        foreach ([0, -1, 31_536_000_000_001] as $invalid) {
            $this->assertInvalid(static fn() => new CacheLifetime($invalid));
        }

        $window = new CacheFreshnessWindow(new CacheInstant(10), new CacheInstant(20));
        self::assertSame(10, $window->lifetimeMicroseconds());
        self::assertSame(CacheFreshness::Fresh, $window->freshnessAt(new CacheInstant(10)));
        self::assertSame(CacheFreshness::Fresh, $window->freshnessAt(new CacheInstant(19)));
        self::assertSame(CacheFreshness::Expired, $window->freshnessAt(new CacheInstant(20)));
        self::assertSame(CacheFreshness::Expired, $window->freshnessAt(new CacheInstant(21)));
        self::assertTrue($window->equals(new CacheFreshnessWindow(new CacheInstant(10), new CacheInstant(20))));
        $this->assertInvalid(static fn() => $window->freshnessAt(new CacheInstant(9)));
        $this->assertInvalid(static fn() => new CacheFreshnessWindow(new CacheInstant(10), new CacheInstant(10)));
        $this->assertInvalid(static fn() => new CacheFreshnessWindow(new CacheInstant(10), new CacheInstant(9)));
        $this->assertInvalid(static fn() => new CacheFreshnessWindow(new CacheInstant(0), new CacheInstant(31_536_000_000_001)));
    }

    private function assertInvalid(callable $construct): void
    {
        try {
            $construct();
            self::fail('Expected freshness construction to fail.');
        } catch (InvalidCacheContractException) {
            self::addToAssertionCount(1);
        }
    }
}
