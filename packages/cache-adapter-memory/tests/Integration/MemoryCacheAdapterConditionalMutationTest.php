<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterMemory\Tests\Integration;

use Kongono\CacheAdapterContract\Adapter\CacheAdapterEntry;
use Kongono\CacheAdapterContract\Request\CacheAdapterLookupRequest;
use Kongono\CacheAdapterContract\Request\CacheAdapterRemoveRequest;
use Kongono\CacheAdapterContract\Request\CacheAdapterReplaceRequest;
use Kongono\CacheAdapterContract\Request\CacheAdapterStoreRequest;
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
use Kongono\CacheContract\Version\CacheEntryVersion;
use PHPUnit\Framework\TestCase;

final class MemoryCacheAdapterConditionalMutationTest extends TestCase
{
    public function testReplaceAndRemoveBindToExactVersionWithoutChangingFailureState(): void
    {
        $adapter = $this->adapter();
        $first = $adapter->store(new CacheAdapterStoreRequest($this->entry('item', 'first')));
        $firstVersion = $first->entryEvidence()?->entryVersion();
        self::assertNotNull($firstVersion);
        $wrong = new CacheEntryVersion(str_repeat('a', 64));
        self::assertSame(CacheMutationOutcome::Conflict, $adapter->replace(new CacheAdapterReplaceRequest($this->entry('item', 'second'), $wrong))->outcome());
        self::assertSame('first', $adapter->lookup(new CacheAdapterLookupRequest($this->key('item')))->entryEvidence()?->valueBytes()->bytes());
        $replacement = $adapter->replace(new CacheAdapterReplaceRequest($this->entry('item', 'second'), $firstVersion));
        self::assertSame(CacheMutationOutcome::Stored, $replacement->outcome());
        self::assertFalse($firstVersion->equals($replacement->entryEvidence()?->entryVersion() ?? $firstVersion));
        self::assertSame(CacheMutationOutcome::Conflict, $adapter->remove(new CacheAdapterRemoveRequest($this->key('item'), $firstVersion))->outcome());
        $currentVersion = $replacement->entryEvidence()?->entryVersion();
        self::assertNotNull($currentVersion);
        self::assertSame(CacheMutationOutcome::Removed, $adapter->remove(new CacheAdapterRemoveRequest($this->key('item'), $currentVersion))->outcome());
        self::assertSame(CacheMutationOutcome::NotFound, $adapter->remove(new CacheAdapterRemoveRequest($this->key('item'), $currentVersion))->outcome());
        self::assertSame(CacheMutationOutcome::NotFound, $adapter->replace(new CacheAdapterReplaceRequest($this->entry('missing', 'value'), $currentVersion))->outcome());
    }

    private function adapter(): BoundedMemoryCacheAdapter
    {
        return new BoundedMemoryCacheAdapter(new MemoryAdapterCapacity(4, 64, 32), MemoryEvictionPolicy::OldestInsertionFirst);
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
