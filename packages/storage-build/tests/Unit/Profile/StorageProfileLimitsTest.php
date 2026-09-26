<?php

declare(strict_types=1);

namespace Kongono\StorageBuild\Tests\Unit\Profile;

use Kongono\StorageBuild\Exception\InvalidStorageProfileException;
use Kongono\StorageBuild\Profile\StorageProfileLimits;
use Kongono\StorageContract\Bound\StorageContractBounds;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StorageProfileLimitsTest extends TestCase
{
    public function testLowerAndUpperBoundsAreExact(): void
    {
        $lower = new StorageProfileLimits(1, 1, 1);
        self::assertSame(1, $lower->maxChunkBytes());
        self::assertSame(1, $lower->maxObjectBytes());
        self::assertSame(1, $lower->maxListPageSize());

        $upper = new StorageProfileLimits(
            StorageContractBounds::MAX_CHUNK_BYTES,
            StorageContractBounds::MAX_OBJECT_BYTES,
            StorageContractBounds::MAX_LIST_PAGE_SIZE,
        );
        self::assertSame(StorageContractBounds::MAX_CHUNK_BYTES, $upper->maxChunkBytes());
        self::assertSame(StorageContractBounds::MAX_OBJECT_BYTES, $upper->maxObjectBytes());
        self::assertSame(StorageContractBounds::MAX_LIST_PAGE_SIZE, $upper->maxListPageSize());
        self::assertTrue($upper->equals(new StorageProfileLimits(
            StorageContractBounds::MAX_CHUNK_BYTES,
            StorageContractBounds::MAX_OBJECT_BYTES,
            StorageContractBounds::MAX_LIST_PAGE_SIZE,
        )));
    }

    #[DataProvider('invalidLimits')]
    public function testInvalidBoundsAndRelationsFailClosed(int $chunk, int $object, int $page): void
    {
        $this->expectException(InvalidStorageProfileException::class);
        new StorageProfileLimits($chunk, $object, $page);
    }

    /** @return iterable<string, array{int, int, int}> */
    public static function invalidLimits(): iterable
    {
        yield 'chunk-zero' => [0, 1, 1];
        yield 'object-zero' => [1, 0, 1];
        yield 'page-zero' => [1, 1, 0];
        yield 'chunk-negative' => [-1, 1, 1];
        yield 'chunk-overflow' => [StorageContractBounds::MAX_CHUNK_BYTES + 1, StorageContractBounds::MAX_OBJECT_BYTES, 1];
        yield 'object-overflow' => [1, StorageContractBounds::MAX_OBJECT_BYTES + 1, 1];
        yield 'page-overflow' => [1, 1, StorageContractBounds::MAX_LIST_PAGE_SIZE + 1];
        yield 'chunk-greater-than-object' => [2, 1, 1];
    }
}
