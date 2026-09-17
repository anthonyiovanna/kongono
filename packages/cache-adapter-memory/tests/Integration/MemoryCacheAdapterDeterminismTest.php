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

final class MemoryCacheAdapterDeterminismTest extends TestCase
{
    public function testEqualFreshInstancesProduceEqualDeterministicVersions(): void
    {
        $left = $this->adapter(2, 8, 4);
        $right = $this->adapter(2, 8, 4);
        $entry = $this->entry('item', 'data');
        $leftVersion = $left->store(new CacheAdapterStoreRequest($entry))->entryEvidence()?->entryVersion();
        $rightVersion = $right->store(new CacheAdapterStoreRequest($entry))->entryEvidence()?->entryVersion();
        self::assertNotNull($leftVersion);
        self::assertNotNull($rightVersion);
        self::assertTrue($leftVersion->equals($rightVersion));
        self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/D', $leftVersion->token());
    }

    public function testFailedOperationsDoNotConsumeVersionAndRejectedEntryDoesNotMutate(): void
    {
        $withFailure = $this->adapter(2, 4, 4);
        self::assertSame(CacheMutationOutcome::Rejected, $withFailure->store(new CacheAdapterStoreRequest($this->entry('large', '12345')))->outcome());
        $entry = $this->entry('item', 'data');
        $afterFailure = $withFailure->store(new CacheAdapterStoreRequest($entry))->entryEvidence()?->entryVersion();
        $fresh = $this->adapter(2, 4, 4)->store(new CacheAdapterStoreRequest($entry))->entryEvidence()?->entryVersion();
        self::assertNotNull($afterFailure);
        self::assertNotNull($fresh);
        self::assertTrue($afterFailure->equals($fresh));
        self::assertSame(CacheAdapterLookupStatus::Miss, $withFailure->lookup(new CacheAdapterLookupRequest($this->key('large')))->status());
    }

    public function testAggregateCapacityEvictsUntilExactBoundHolds(): void
    {
        $adapter = $this->adapter(4, 6, 4);
        $adapter->store(new CacheAdapterStoreRequest($this->entry('one', '111')));
        $adapter->store(new CacheAdapterStoreRequest($this->entry('two', '222')));
        $adapter->store(new CacheAdapterStoreRequest($this->entry('three', '333')));
        self::assertSame(CacheAdapterLookupStatus::Miss, $adapter->lookup(new CacheAdapterLookupRequest($this->key('one')))->status());
        self::assertSame(CacheAdapterLookupStatus::Found, $adapter->lookup(new CacheAdapterLookupRequest($this->key('two')))->status());
        self::assertSame(CacheAdapterLookupStatus::Found, $adapter->lookup(new CacheAdapterLookupRequest($this->key('three')))->status());
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
