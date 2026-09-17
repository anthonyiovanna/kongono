<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterContract\Tests\Unit\Adapter;

use Kongono\CacheAdapterContract\Adapter\CacheAdapterLimits;
use Kongono\CacheAdapterContract\Exception\InvalidCacheAdapterContractException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CacheAdapterLimitsTest extends TestCase
{
    public function testBoundaryLimitsAreAccepted(): void
    {
        $limits = new CacheAdapterLimits(4_096, 67_108_864, 1_048_576);
        self::assertSame(4_096, $limits->maximumEntries());
        self::assertSame(67_108_864, $limits->maximumAggregateValueBytes());
        self::assertSame(1_048_576, $limits->maximumValueBytes());
    }

    #[DataProvider('invalidLimits')]
    public function testInvalidRelationsFailClosed(int $entries, int $aggregate, int $single): void
    {
        $this->expectException(InvalidCacheAdapterContractException::class);
        new CacheAdapterLimits($entries, $aggregate, $single);
    }

    /** @return iterable<array{int, int, int}> */
    public static function invalidLimits(): iterable
    {
        yield [0, 1, 1];
        yield [4_097, 1, 1];
        yield [1, 67_108_865, 1];
        yield [1, 1, 2];
        yield [1, 1_048_577, 1_048_577];
    }
}
