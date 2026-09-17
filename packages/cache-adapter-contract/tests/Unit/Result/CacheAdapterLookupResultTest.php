<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterContract\Tests\Unit\Result;

use Kongono\CacheAdapterContract\Exception\InvalidCacheAdapterContractException;
use Kongono\CacheAdapterContract\Result\CacheAdapterLookupResult;
use Kongono\CacheAdapterContract\Result\CacheAdapterLookupStatus;
use Kongono\CacheAdapterContract\Tests\Fixture\FakeCacheAdapter;
use PHPUnit\Framework\TestCase;

final class CacheAdapterLookupResultTest extends TestCase
{
    public function testOnlyFoundCarriesMatchingEvidence(): void
    {
        $entry = FakeCacheAdapter::entry();
        $found = CacheAdapterLookupResult::found($entry->keyIdentity(), FakeCacheAdapter::evidence($entry));
        self::assertSame(CacheAdapterLookupStatus::Found, $found->status());
        self::assertNotNull($found->entryEvidence());
        self::assertNull(CacheAdapterLookupResult::miss($entry->keyIdentity())->entryEvidence());
    }

    public function testForeignKeyEvidenceIsRejected(): void
    {
        $this->expectException(InvalidCacheAdapterContractException::class);
        $entry = FakeCacheAdapter::entry('first');
        CacheAdapterLookupResult::found(FakeCacheAdapter::key('second'), FakeCacheAdapter::evidence($entry));
    }

    public function testNonFoundEvidenceIsRejected(): void
    {
        $this->expectException(InvalidCacheAdapterContractException::class);
        $entry = FakeCacheAdapter::entry();
        new CacheAdapterLookupResult(CacheAdapterLookupStatus::Unavailable, $entry->keyIdentity(), FakeCacheAdapter::evidence($entry));
    }
}
