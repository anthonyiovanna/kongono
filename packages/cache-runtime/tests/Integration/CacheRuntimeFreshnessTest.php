<?php

declare(strict_types=1);

namespace Kongono\CacheRuntime\Tests\Integration;

use Kongono\CacheAdapterContract\Adapter\CacheAdapterEntry;
use Kongono\CacheAdapterContract\Adapter\CacheAdapterIdentity;
use Kongono\CacheAdapterContract\Result\CacheAdapterLookupResult;
use Kongono\CacheBuild\Build\CacheProfileBuilder;
use Kongono\CacheBuild\Profile\CacheProfileDeclaration;
use Kongono\CacheBuild\Profile\CacheProfileName;
use Kongono\CacheBuild\Profile\CacheProfileSet;
use Kongono\CacheBuild\Profile\CacheProfileValueLimit;
use Kongono\CacheContract\Codec\CacheCodecIdentity;
use Kongono\CacheContract\ContractVersion;
use Kongono\CacheContract\Freshness\CacheFreshnessWindow;
use Kongono\CacheContract\Freshness\CacheLifetime;
use Kongono\CacheContract\Identity\CacheKeyIdentity;
use Kongono\CacheContract\Identity\CacheNamespace;
use Kongono\CacheContract\Identity\LogicalCacheKey;
use Kongono\CacheContract\Outcome\CacheLookupOutcome;
use Kongono\CacheContract\Time\CacheInstant;
use Kongono\CacheContract\Value\CacheValueBytes;
use Kongono\CacheRuntime\Binding\CacheRuntimeBinding;
use Kongono\CacheRuntime\Exception\InvalidCacheRuntimeException;
use Kongono\CacheRuntime\Operation\CacheLookupRequest;
use Kongono\CacheRuntime\Runtime\ExactCurrentCacheRuntime;
use Kongono\CacheRuntime\Tests\Fixture\ScriptedCacheAdapter;
use PHPUnit\Framework\TestCase;

final class CacheRuntimeFreshnessTest extends TestCase
{
    public function testClosedLookupOutcomesAndExactExpiryBoundary(): void
    {
        $key = self::key();
        $entry = self::entry($key, 10, 20);
        $adapter = (new ScriptedCacheAdapter())
            ->queueLookup(CacheAdapterLookupResult::miss($key))
            ->queueLookup(CacheAdapterLookupResult::rejected($key))
            ->queueLookup(CacheAdapterLookupResult::unavailable($key))
            ->queueLookup(CacheAdapterLookupResult::found($key, ScriptedCacheAdapter::evidence($entry)))
            ->queueLookup(CacheAdapterLookupResult::found($key, ScriptedCacheAdapter::evidence($entry)));
        $runtime = self::runtime($adapter);
        self::assertSame(CacheLookupOutcome::Miss, $runtime->lookup(self::request(10))->outcome());
        self::assertSame(CacheLookupOutcome::Rejected, $runtime->lookup(self::request(10))->outcome());
        self::assertSame(CacheLookupOutcome::Unavailable, $runtime->lookup(self::request(10))->outcome());
        $fresh = $runtime->lookup(self::request(19));
        self::assertSame(CacheLookupOutcome::FreshHit, $fresh->outcome());
        self::assertSame('data', $fresh->entryEvidence()?->valueBytes()->bytes());
        $expired = $runtime->lookup(self::request(20));
        self::assertSame(CacheLookupOutcome::Expired, $expired->outcome());
        self::assertNull($expired->entryEvidence());
    }

    public function testNowBeforeStoredAtFailsClosed(): void
    {
        $key = self::key();
        $adapter = (new ScriptedCacheAdapter())->queueLookup(CacheAdapterLookupResult::found($key, ScriptedCacheAdapter::evidence(self::entry($key, 10, 20))));
        $this->expectException(InvalidCacheRuntimeException::class);
        self::runtime($adapter)->lookup(self::request(9));
    }

    private static function runtime(ScriptedCacheAdapter $adapter): ExactCurrentCacheRuntime
    {
        $set = new CacheProfileSet([new CacheProfileDeclaration(ContractVersion::Version1, new CacheProfileName('primary'), new CacheNamespace('runtime-tests'), new CacheCodecIdentity('text', 1), new CacheLifetime(10), new CacheProfileValueLimit(4))]);

        return new ExactCurrentCacheRuntime(new CacheRuntimeBinding($set, (new CacheProfileBuilder())->build($set)->artifact()->bytes(), new CacheProfileName('primary'), new CacheAdapterIdentity('scripted', 1), $adapter));
    }

    private static function request(int $now): CacheLookupRequest
    {
        return new CacheLookupRequest(new LogicalCacheKey('item'), new CacheInstant($now));
    }

    private static function key(): CacheKeyIdentity
    {
        return CacheKeyIdentity::from(new CacheNamespace('runtime-tests'), new LogicalCacheKey('item'));
    }

    private static function entry(CacheKeyIdentity $key, int $stored, int $expires): CacheAdapterEntry
    {
        $value = new CacheValueBytes('data');

        return new CacheAdapterEntry(ContractVersion::Version1, $key, new CacheCodecIdentity('text', 1), $value, $value->contentIdentity(), new CacheFreshnessWindow(new CacheInstant($stored), new CacheInstant($expires)));
    }
}
