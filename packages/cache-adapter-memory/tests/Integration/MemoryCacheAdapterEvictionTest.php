<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterMemory\Tests\Integration;

use Kongono\CacheAdapterContract\Adapter\CacheAdapterEntry;
use Kongono\CacheAdapterContract\Request\CacheAdapterLookupRequest;
use Kongono\CacheAdapterContract\Request\CacheAdapterReplaceRequest;
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
use Kongono\CacheContract\Time\CacheInstant;
use Kongono\CacheContract\Value\CacheValueBytes;
use PHPUnit\Framework\TestCase;

final class MemoryCacheAdapterEvictionTest extends TestCase
{
    public function testLookupDoesNotRefreshAndOldestInsertionIsEvicted(): void
    {
        $adapter = $this->adapter(2, 8, 4);
        $adapter->store(new CacheAdapterStoreRequest($this->entry('first', '1111')));
        $adapter->store(new CacheAdapterStoreRequest($this->entry('second', '2222')));
        $adapter->lookup(new CacheAdapterLookupRequest($this->key('first')));
        $adapter->store(new CacheAdapterStoreRequest($this->entry('third', '3333')));
        self::assertSame(CacheAdapterLookupStatus::Miss, $adapter->lookup(new CacheAdapterLookupRequest($this->key('first')))->status());
        self::assertSame(CacheAdapterLookupStatus::Found, $adapter->lookup(new CacheAdapterLookupRequest($this->key('second')))->status());
    }

    public function testReplaceBecomesNewestForEvictionOrder(): void
    {
        $adapter = $this->adapter(2, 8, 4);
        $first = $adapter->store(new CacheAdapterStoreRequest($this->entry('first', '1111')));
        $adapter->store(new CacheAdapterStoreRequest($this->entry('second', '2222')));
        $version = $first->entryEvidence()?->entryVersion();
        self::assertNotNull($version);
        $adapter->replace(new CacheAdapterReplaceRequest($this->entry('first', 'aaaa'), $version));
        $adapter->store(new CacheAdapterStoreRequest($this->entry('third', '3333')));
        self::assertSame(CacheAdapterLookupStatus::Found, $adapter->lookup(new CacheAdapterLookupRequest($this->key('first')))->status());
        self::assertSame(CacheAdapterLookupStatus::Miss, $adapter->lookup(new CacheAdapterLookupRequest($this->key('second')))->status());
    }

    private function adapter(int $entries, int $aggregate, int $single): BoundedMemoryCacheAdapter
    {
        return new BoundedMemoryCacheAdapter(new MemoryAdapterCapacity($entries, $aggregate, $single), MemoryEvictionPolicy::OldestInsertionFirst);
    }

    private function entry(string $key, string $bytes): CacheAdapterEntry
    {
        $value = new CacheValueBytes($bytes);

        return new CacheAdapterEntry(ContractVersion::Version1, $this->key($key), new CacheCodecIdentity('text', 1), $value, $value->contentIdentity(), new CacheFreshnessWindow(new CacheInstant(10), new CacheInstant(20)));
    }

    private function key(string $key): CacheKeyIdentity
    {
        return CacheKeyIdentity::from(new CacheNamespace('tests'), new LogicalCacheKey($key));
    }
}
