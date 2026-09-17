<?php

declare(strict_types=1);

namespace Kongono\CacheContract\Tests\Unit\Time;

use Kongono\CacheContract\Exception\InvalidCacheContractException;
use Kongono\CacheContract\Time\CacheInstant;
use PHPUnit\Framework\TestCase;

final class CacheInstantTest extends TestCase
{
    public function testCallerSuppliedBoundsOrderingAndEqualityAreExact(): void
    {
        $minimum = new CacheInstant(0);
        $maximum = new CacheInstant(253_402_300_799_999_999);
        self::assertSame(0, $minimum->microseconds());
        self::assertSame(253_402_300_799_999_999, $maximum->microseconds());
        self::assertLessThan(0, $minimum->compareTo($maximum));
        self::assertTrue($minimum->equals(new CacheInstant(0)));
        foreach ([-1, 253_402_300_800_000_000] as $invalid) {
            try {
                new CacheInstant($invalid);
                self::fail('Expected instant bound to fail.');
            } catch (InvalidCacheContractException) {
                self::addToAssertionCount(1);
            }
        }
    }
}
