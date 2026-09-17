<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterMemory\Tests\Integration;

use Fiber;
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
use Kongono\CacheContract\Time\CacheInstant;
use Kongono\CacheContract\Value\CacheValueBytes;
use PHPUnit\Framework\TestCase;

final class MemoryCacheAdapterIsolationTest extends TestCase
{
    public function testInstancesRemainIsolatedAndEvidenceSurvivesEviction(): void
    {
        $left = $this->adapter();
        $right = $this->adapter();
        $stored = $left->store(new CacheAdapterStoreRequest($this->entry('one', '1111')))->entryEvidence();
        self::assertNotNull($stored);
        self::assertSame(CacheAdapterLookupStatus::Miss, $right->lookup(new CacheAdapterLookupRequest($this->key('one')))->status());
        $left->store(new CacheAdapterStoreRequest($this->entry('two', '2222')));
        self::assertSame(CacheAdapterLookupStatus::Miss, $left->lookup(new CacheAdapterLookupRequest($this->key('one')))->status());
        self::assertSame('1111', $stored->valueBytes()->bytes());
    }

    public function testFiberInterleavingAndRepeatedOperationsLeakNoScratchState(): void
    {
        $adapter = new BoundedMemoryCacheAdapter(new MemoryAdapterCapacity(4, 16, 4), MemoryEvictionPolicy::OldestInsertionFirst);
        $first = new Fiber(function () use ($adapter): void {
            $adapter->store(new CacheAdapterStoreRequest($this->entry('first', '1111')));
            Fiber::suspend();
            self::assertSame(CacheAdapterLookupStatus::Found, $adapter->lookup(new CacheAdapterLookupRequest($this->key('second')))->status());
        });
        $second = new Fiber(function () use ($adapter): void {
            $adapter->store(new CacheAdapterStoreRequest($this->entry('second', '2222')));
            Fiber::suspend();
            self::assertSame(CacheAdapterLookupStatus::Found, $adapter->lookup(new CacheAdapterLookupRequest($this->key('first')))->status());
        });
        $first->start();
        $second->start();
        $first->resume();
        $second->resume();
        self::assertTrue($first->isTerminated());
        self::assertTrue($second->isTerminated());
        for ($iteration = 0; $iteration < 8; ++$iteration) {
            self::assertSame(CacheAdapterLookupStatus::Found, $adapter->lookup(new CacheAdapterLookupRequest($this->key('first')))->status());
        }
    }

    private function adapter(): BoundedMemoryCacheAdapter
    {
        return new BoundedMemoryCacheAdapter(new MemoryAdapterCapacity(1, 4, 4), MemoryEvictionPolicy::OldestInsertionFirst);
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
