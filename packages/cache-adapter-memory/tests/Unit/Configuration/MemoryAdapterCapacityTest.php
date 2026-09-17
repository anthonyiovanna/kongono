<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterMemory\Tests\Unit\Configuration;

use Kongono\CacheAdapterMemory\Configuration\MemoryAdapterCapacity;
use Kongono\CacheAdapterMemory\Exception\InvalidMemoryCacheAdapterException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MemoryAdapterCapacityTest extends TestCase
{
    public function testEqualityBoundariesAreAccepted(): void
    {
        $capacity = new MemoryAdapterCapacity(4_096, 67_108_864, 1_048_576);
        self::assertSame(4_096, $capacity->maximumEntries());
        self::assertSame(67_108_864, $capacity->maximumAggregateValueBytes());
        self::assertSame(1_048_576, $capacity->maximumValueBytes());
        self::assertSame(4_096, $capacity->limits()->maximumEntries());
    }

    #[DataProvider('invalidCapacities')]
    public function testPlusOneAndInvalidRelationsFailClosed(int $entries, int $aggregate, int $single): void
    {
        $this->expectException(InvalidMemoryCacheAdapterException::class);
        new MemoryAdapterCapacity($entries, $aggregate, $single);
    }

    /** @return iterable<array{int, int, int}> */
    public static function invalidCapacities(): iterable
    {
        yield [0, 1, 1];
        yield [4_097, 1, 1];
        yield [1, 67_108_865, 1];
        yield [1, 1_048_577, 1_048_577];
        yield [1, 1, 2];
    }
}
