<?php

declare(strict_types=1);

namespace CacheMinimalApp;

use Kongono\CacheAdapterMemory\Adapter\BoundedMemoryCacheAdapter;
use Kongono\CacheAdapterMemory\Configuration\MemoryAdapterCapacity;
use Kongono\CacheAdapterMemory\Configuration\MemoryEvictionPolicy;
use Kongono\CacheApplication\Mapping\CacheProfileCodecMapping;
use Kongono\CacheApplication\Runtime\CacheApplicationRuntimeFactory;
use Kongono\CacheBuild\Profile\CacheProfileName;
use Kongono\CacheContract\Identity\LogicalCacheKey;
use Kongono\CacheContract\Time\CacheInstant;
use Kongono\CacheRuntime\Operation\CacheLookupRequest;
use Kongono\CacheRuntime\Operation\CacheRemoveRequest;
use Kongono\CacheRuntime\Operation\CacheReplaceRequest;
use Kongono\CacheRuntime\Operation\CacheStoreRequest;
use RuntimeException;

/** Demonstrates disposable cache evidence while application truth remains independent. */
final class CacheDemo
{
    /** @return array<string, string> */
    public function run(string $root): array
    {
        $current = (new CacheWorkflow($root))->build();
        $codec = new ApplicationJsonCodec();
        $mapping = new CacheProfileCodecMapping(new CacheProfileName('application'), $codec->identity());
        $adapter = new BoundedMemoryCacheAdapter(new MemoryAdapterCapacity(4, 4096, 1024), MemoryEvictionPolicy::OldestInsertionFirst);
        $runtime = (new CacheApplicationRuntimeFactory())->create($current, $mapping, $adapter->identity(), $adapter);
        $key = new LogicalCacheKey('example');
        $miss = $runtime->lookup(new CacheLookupRequest($key, new CacheInstant(100)));
        $stored = $runtime->store(new CacheStoreRequest($key, $codec->encode(['message' => 'cached']), new CacheInstant(100)));
        $storedEvidence = $stored->entryEvidence();
        if (null === $storedEvidence) {
            throw new RuntimeException('Expected stored evidence.');
        }
        $hit = $runtime->lookup(new CacheLookupRequest($key, new CacheInstant(101)));
        $hitEvidence = $hit->entryEvidence();
        if (null === $hitEvidence) {
            throw new RuntimeException('Expected fresh evidence.');
        }
        $codec->decode($hitEvidence->valueBytes());
        $expired = $runtime->lookup(new CacheLookupRequest($key, new CacheInstant(1_000_101)));
        $replaced = $runtime->replace(new CacheReplaceRequest($key, $codec->encode(['message' => 'renewed']), $storedEvidence->entryVersion(), new CacheInstant(2_000_000)));
        $replacedEvidence = $replaced->entryEvidence();
        if (null === $replacedEvidence) {
            throw new RuntimeException('Expected replacement evidence.');
        }
        $removed = $runtime->remove(new CacheRemoveRequest($key, $replacedEvidence->entryVersion(), new CacheInstant(2_000_001)));
        $replacement = new BoundedMemoryCacheAdapter(new MemoryAdapterCapacity(4, 4096, 1024), MemoryEvictionPolicy::OldestInsertionFirst);
        $lost = (new CacheApplicationRuntimeFactory())->create($current, $mapping, $replacement->identity(), $replacement)->lookup(new CacheLookupRequest($key, new CacheInstant(2_000_002)));

        return [
            'application_truth' => 'available',
            'expired' => $expired->outcome()->value,
            'fresh' => $hit->outcome()->value,
            'loss' => $lost->outcome()->value,
            'removed' => $removed->outcome()->value,
            'store' => $stored->outcome()->value,
            'initial' => $miss->outcome()->value,
        ];
    }
}
