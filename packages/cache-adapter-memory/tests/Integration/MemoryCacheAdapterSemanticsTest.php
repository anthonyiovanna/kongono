<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterMemory\Tests\Integration;

use Kongono\CacheAdapterContract\Adapter\CacheAdapterEntry;
use Kongono\CacheAdapterContract\Request\CacheAdapterLookupRequest;
use Kongono\CacheAdapterContract\Request\CacheAdapterStoreRequest;
use Kongono\CacheAdapterContract\Result\CacheAdapterLookupStatus;
use Kongono\CacheAdapterMemory\Adapter\BoundedMemoryCacheAdapter;
use Kongono\CacheAdapterMemory\Configuration\MemoryAdapterCapacity;
use Kongono\CacheAdapterMemory\Configuration\MemoryEvictionPolicy;
use Kongono\CacheContract\Codec\CacheCodecIdentity;
use Kongono\CacheContract\ContractVersion;
use Kongono\CacheContract\Freshness\CacheFreshnessWindow;
use Kongono\CacheContract\Identity\CacheKeyIdentity;
use Kongono\CacheContract\Identity\CacheNamespace;
use Kongono\CacheContract\Identity\LogicalCacheKey;
use Kongono\CacheContract\Outcome\CacheMutationOutcome;
use Kongono\CacheContract\Time\CacheInstant;
use Kongono\CacheContract\Value\CacheValueBytes;
use PHPUnit\Framework\TestCase;

final class MemoryCacheAdapterSemanticsTest extends TestCase
{
    public function testStoreFoundMissEmptyBytesAndConflictSemantics(): void
    {
        $adapter = $this->adapter();
        $entry = $this->entry('item', '');
        self::assertSame(CacheAdapterLookupStatus::Miss, $adapter->lookup(new CacheAdapterLookupRequest($entry->keyIdentity()))->status());
        $stored = $adapter->store(new CacheAdapterStoreRequest($entry));
        self::assertSame(CacheMutationOutcome::Stored, $stored->outcome());
        self::assertSame(0, $stored->entryEvidence()?->valueBytes()->byteCount());
        self::assertSame(CacheAdapterLookupStatus::Found, $adapter->lookup(new CacheAdapterLookupRequest($entry->keyIdentity()))->status());
        self::assertSame(CacheMutationOutcome::Conflict, $adapter->store(new CacheAdapterStoreRequest($this->entry('item', 'other')))->outcome());
        self::assertSame('', $adapter->lookup(new CacheAdapterLookupRequest($entry->keyIdentity()))->entryEvidence()?->valueBytes()->bytes());
    }

    public function testFreshnessIsStoredExactlyButNeverEvaluated(): void
    {
        $adapter = $this->adapter();
        $entry = $this->entry('expired-evidence', 'value', 1, 2);
        $adapter->store(new CacheAdapterStoreRequest($entry));
        $result = $adapter->lookup(new CacheAdapterLookupRequest($entry->keyIdentity()));
        self::assertSame(CacheAdapterLookupStatus::Found, $result->status());
        $evidence = $result->entryEvidence();
        self::assertNotNull($evidence);
        self::assertSame(1, $evidence->freshnessWindow()->storedAt()->microseconds());
        self::assertSame(2, $evidence->freshnessWindow()->expiresAt()->microseconds());
    }

    private function adapter(): BoundedMemoryCacheAdapter
    {
        return new BoundedMemoryCacheAdapter(new MemoryAdapterCapacity(4, 32, 16), MemoryEvictionPolicy::OldestInsertionFirst);
    }

    private function entry(string $key, string $bytes, int $storedAt = 10, int $expiresAt = 20): CacheAdapterEntry
    {
        $value = new CacheValueBytes($bytes);

        return new CacheAdapterEntry(ContractVersion::Version1, $this->key($key), new CacheCodecIdentity('text', 1), $value, $value->contentIdentity(), new CacheFreshnessWindow(new CacheInstant($storedAt), new CacheInstant($expiresAt)));
    }

    private function key(string $key): CacheKeyIdentity
    {
        return CacheKeyIdentity::from(new CacheNamespace('tests'), new LogicalCacheKey($key));
    }
}
