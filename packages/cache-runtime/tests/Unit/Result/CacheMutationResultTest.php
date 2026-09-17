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
use Kongono\CacheContract\Outcome\CacheMutationOutcome;
use Kongono\CacheContract\Time\CacheInstant;
use Kongono\CacheContract\Value\CacheValueBytes;
use Kongono\CacheContract\Version\CacheEntryVersion;
use Kongono\CacheRuntime\Exception\InvalidCacheRuntimeException;
use Kongono\CacheRuntime\Result\CacheMutationResult;
use PHPUnit\Framework\TestCase;

final class CacheMutationResultTest extends TestCase
{
    public function testOnlyStoredCarriesEvidence(): void
    {
        $key = CacheKeyIdentity::from(new CacheNamespace('runtime-tests'), new LogicalCacheKey('item'));
        $value = new CacheValueBytes('data');
        $entry = new CacheEntryEvidence(ContractVersion::Version1, $key, new CacheCodecIdentity('text', 1), $value, $value->contentIdentity(), new CacheFreshnessWindow(new CacheInstant(10), new CacheInstant(20)), new CacheEntryVersion(str_repeat('a', 64)));
        self::assertSame($entry, (new CacheMutationResult(CacheMutationOutcome::Stored, $key, $entry))->entryEvidence());
        foreach ([CacheMutationOutcome::Removed, CacheMutationOutcome::NotFound, CacheMutationOutcome::Conflict, CacheMutationOutcome::Rejected, CacheMutationOutcome::Unavailable] as $outcome) {
            self::assertNull((new CacheMutationResult($outcome, $key))->entryEvidence());
        }
        $this->expectException(InvalidCacheRuntimeException::class);
        new CacheMutationResult(CacheMutationOutcome::Conflict, $key, $entry);
    }
}
