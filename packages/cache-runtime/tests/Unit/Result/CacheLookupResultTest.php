<?php

declare(strict_types=1);

namespace Kongono\CacheRuntime\Tests\Unit\Result;

use Kongono\CacheContract\Codec\CacheCodecIdentity;
use Kongono\CacheContract\ContractVersion;
use Kongono\CacheContract\Entry\CacheEntryEvidence;
use Kongono\CacheContract\Freshness\CacheFreshnessWindow;
use Kongono\CacheContract\Identity\CacheKeyIdentity;
use Kongono\CacheContract\Identity\CacheNamespace;
use Kongono\CacheContract\Identity\LogicalCacheKey;
use Kongono\CacheContract\Outcome\CacheLookupOutcome;
use Kongono\CacheContract\Time\CacheInstant;
use Kongono\CacheContract\Value\CacheValueBytes;
use Kongono\CacheContract\Version\CacheEntryVersion;
use Kongono\CacheRuntime\Exception\InvalidCacheRuntimeException;
use Kongono\CacheRuntime\Result\CacheLookupResult;
use PHPUnit\Framework\TestCase;

final class CacheLookupResultTest extends TestCase
{
    public function testOnlyFreshHitCarriesEvidence(): void
    {
        $key = self::key();
        $entry = self::entry($key);
        $result = new CacheLookupResult(CacheLookupOutcome::FreshHit, $key, $entry);
        self::assertSame(CacheLookupOutcome::FreshHit, $result->outcome());
        self::assertSame($key, $result->keyIdentity());
        self::assertSame($entry, $result->entryEvidence());
        foreach ([CacheLookupOutcome::Miss, CacheLookupOutcome::Expired, CacheLookupOutcome::Rejected, CacheLookupOutcome::Unavailable] as $outcome) {
            self::assertNull((new CacheLookupResult($outcome, $key))->entryEvidence());
        }
        $this->expectException(InvalidCacheRuntimeException::class);
        new CacheLookupResult(CacheLookupOutcome::Expired, $key, $entry);
    }

    private static function key(): CacheKeyIdentity
    {
        return CacheKeyIdentity::from(new CacheNamespace('runtime-tests'), new LogicalCacheKey('item'));
    }

    private static function entry(CacheKeyIdentity $key): CacheEntryEvidence
    {
        $value = new CacheValueBytes('data');

        return new CacheEntryEvidence(ContractVersion::Version1, $key, new CacheCodecIdentity('text', 1), $value, $value->contentIdentity(), new CacheFreshnessWindow(new CacheInstant(10), new CacheInstant(20)), new CacheEntryVersion(str_repeat('a', 64)));
    }
}
