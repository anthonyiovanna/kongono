<?php

declare(strict_types=1);

namespace Kongono\CacheApplication\Tests\Unit\Runtime;

use Kongono\CacheAdapterContract\Adapter\CacheAdapterIdentity;
use Kongono\CacheAdapterMemory\Adapter\BoundedMemoryCacheAdapter;
use Kongono\CacheAdapterMemory\Configuration\MemoryAdapterCapacity;
use Kongono\CacheAdapterMemory\Configuration\MemoryEvictionPolicy;
use Kongono\CacheApplication\Exception\CacheProfileMappingException;
use Kongono\CacheApplication\Exception\CacheRuntimeCompositionException;
use Kongono\CacheApplication\Mapping\CacheProfileCodecMapping;
use Kongono\CacheApplication\Runtime\CacheApplicationRuntimeFactory;
use Kongono\CacheApplication\Workflow\CurrentCacheApplicationBuild;
use Kongono\CacheBuild\Build\CacheProfileBuilder;
use Kongono\CacheBuild\Profile\CacheProfileDeclaration;
use Kongono\CacheBuild\Profile\CacheProfileName;
use Kongono\CacheBuild\Profile\CacheProfileSet;
use Kongono\CacheBuild\Profile\CacheProfileValueLimit;
use Kongono\CacheContract\Codec\CacheCodecIdentity;
use Kongono\CacheContract\ContractVersion;
use Kongono\CacheContract\Freshness\CacheLifetime;
use Kongono\CacheContract\Identity\CacheNamespace;
use Kongono\CacheContract\Identity\LogicalCacheKey;
use Kongono\CacheContract\Outcome\CacheLookupOutcome;
use Kongono\CacheContract\Outcome\CacheMutationOutcome;
use Kongono\CacheContract\Time\CacheInstant;
use Kongono\CacheContract\Value\CacheValueBytes;
use Kongono\CacheRuntime\Operation\CacheLookupRequest;
use Kongono\CacheRuntime\Operation\CacheRemoveRequest;
use Kongono\CacheRuntime\Operation\CacheReplaceRequest;
use Kongono\CacheRuntime\Operation\CacheStoreRequest;
use PHPUnit\Framework\TestCase;

final class CacheApplicationRuntimeFactoryTest extends TestCase
{
    public function testExactMappingAndSuppliedAdapterComposeBeforeOperations(): void
    {
        [$current, $mapping] = self::current();
        $adapter = new BoundedMemoryCacheAdapter(new MemoryAdapterCapacity(4, 4096, 1024), MemoryEvictionPolicy::OldestInsertionFirst);
        $runtime = (new CacheApplicationRuntimeFactory())->create($current, $mapping, $adapter->identity(), $adapter);
        $key = new LogicalCacheKey('greeting');
        $stored = $runtime->store(new CacheStoreRequest($key, new CacheValueBytes('{"message":"hello"}'), new CacheInstant(100)));
        self::assertSame(CacheMutationOutcome::Stored, $stored->outcome());
        self::assertSame(CacheLookupOutcome::FreshHit, $runtime->lookup(new CacheLookupRequest($key, new CacheInstant(101)))->outcome());
        self::assertSame(CacheLookupOutcome::Expired, $runtime->lookup(new CacheLookupRequest($key, new CacheInstant(1_000_101)))->outcome());
        $storedEvidence = $stored->entryEvidence();
        self::assertNotNull($storedEvidence);
        $replaced = $runtime->replace(new CacheReplaceRequest($key, new CacheValueBytes('{"message":"renewed"}'), $storedEvidence->entryVersion(), new CacheInstant(2_000_000)));
        self::assertSame(CacheMutationOutcome::Stored, $replaced->outcome());
        $replacedEvidence = $replaced->entryEvidence();
        self::assertNotNull($replacedEvidence);
        self::assertSame(CacheMutationOutcome::Removed, $runtime->remove(new CacheRemoveRequest($key, $replacedEvidence->entryVersion(), new CacheInstant(2_000_001)))->outcome());
    }

    public function testCodecMismatchFailsBeforeAdapterUse(): void
    {
        [$current] = self::current();
        $adapter = new BoundedMemoryCacheAdapter(new MemoryAdapterCapacity(4, 4096, 1024), MemoryEvictionPolicy::OldestInsertionFirst);
        $this->expectException(CacheProfileMappingException::class);
        (new CacheApplicationRuntimeFactory())->create($current, new CacheProfileCodecMapping(new CacheProfileName('application'), new CacheCodecIdentity('other', 1)), new CacheAdapterIdentity('bounded-memory', 1), $adapter);
    }

    public function testMissingProfileAndAdapterIdentityMismatchFailBeforeAnyOperation(): void
    {
        [$current, $mapping] = self::current();
        $adapter = new BoundedMemoryCacheAdapter(new MemoryAdapterCapacity(4, 4096, 1024), MemoryEvictionPolicy::OldestInsertionFirst);
        $factory = new CacheApplicationRuntimeFactory();
        try {
            $factory->create($current, new CacheProfileCodecMapping(new CacheProfileName('missing'), new CacheCodecIdentity('application-json', 1)), $adapter->identity(), $adapter);
            self::fail('Expected missing profile rejection.');
        } catch (CacheProfileMappingException $failure) {
            self::assertSame('KGN-CACHE-APPLICATION-2045', $failure->diagnosticId());
        }
        try {
            $factory->create($current, $mapping, new CacheAdapterIdentity('other', 1), $adapter);
            self::fail('Expected adapter identity rejection.');
        } catch (CacheRuntimeCompositionException $failure) {
            self::assertSame('KGN-CACHE-APPLICATION-2046', $failure->diagnosticId());
        }

        $first = $factory->create($current, $mapping, $adapter->identity(), $adapter);
        $replacement = new BoundedMemoryCacheAdapter(new MemoryAdapterCapacity(4, 4096, 1024), MemoryEvictionPolicy::OldestInsertionFirst);
        $second = $factory->create($current, $mapping, $replacement->identity(), $replacement);
        $key = new LogicalCacheKey('isolated');
        self::assertSame(CacheMutationOutcome::Stored, $first->store(new CacheStoreRequest($key, new CacheValueBytes('first'), new CacheInstant(10)))->outcome());
        self::assertSame(CacheLookupOutcome::Miss, $second->lookup(new CacheLookupRequest($key, new CacheInstant(10)))->outcome());
    }

    /** @return array{CurrentCacheApplicationBuild,CacheProfileCodecMapping} */
    private static function current(): array
    {
        $name = new CacheProfileName('application');
        $codec = new CacheCodecIdentity('application-json', 1);
        $set = new CacheProfileSet([new CacheProfileDeclaration(ContractVersion::Version1, $name, new CacheNamespace('application'), $codec, new CacheLifetime(1_000_000), new CacheProfileValueLimit(1024))]);
        $artifact = (new CacheProfileBuilder())->build($set)->artifact();
        return [new CurrentCacheApplicationBuild($set, $artifact), new CacheProfileCodecMapping($name, $codec)];
    }
}
