<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterContract\Tests\Unit\Adapter;

use Kongono\CacheAdapterContract\Adapter\CacheAdapterEntry;
use Kongono\CacheAdapterContract\Exception\InvalidCacheAdapterContractException;
use Kongono\CacheAdapterContract\Tests\Fixture\FakeCacheAdapter;
use Kongono\CacheContract\Identity\CacheContentIdentity;
use PHPUnit\Framework\TestCase;

final class CacheAdapterEntryTest extends TestCase
{
    public function testCompleteEntryPreservesFreshnessWithoutEvaluatingIt(): void
    {
        $entry = FakeCacheAdapter::entry(bytes: '');
        self::assertSame(0, $entry->valueBytes()->byteCount());
        self::assertSame(10, $entry->freshnessWindow()->storedAt()->microseconds());
        self::assertSame(20, $entry->freshnessWindow()->expiresAt()->microseconds());
    }

    public function testContentMismatchFailsClosed(): void
    {
        $entry = FakeCacheAdapter::entry();
        $this->expectException(InvalidCacheAdapterContractException::class);
        new CacheAdapterEntry($entry->contractVersion(), $entry->keyIdentity(), $entry->codecIdentity(), $entry->valueBytes(), new CacheContentIdentity(str_repeat('a', 64)), $entry->freshnessWindow());
    }
}
