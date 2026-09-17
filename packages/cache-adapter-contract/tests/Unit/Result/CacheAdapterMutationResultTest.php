<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterContract\Tests\Unit\Result;

use Kongono\CacheAdapterContract\Exception\InvalidCacheAdapterContractException;
use Kongono\CacheAdapterContract\Result\CacheAdapterMutationResult;
use Kongono\CacheAdapterContract\Tests\Fixture\FakeCacheAdapter;
use Kongono\CacheContract\Outcome\CacheMutationOutcome;
use PHPUnit\Framework\TestCase;

final class CacheAdapterMutationResultTest extends TestCase
{
    public function testOnlyStoredCarriesMatchingEvidence(): void
    {
        $entry = FakeCacheAdapter::entry();
        $stored = CacheAdapterMutationResult::stored($entry->keyIdentity(), FakeCacheAdapter::evidence($entry));
        self::assertSame(CacheMutationOutcome::Stored, $stored->outcome());
        self::assertNotNull($stored->entryEvidence());
        foreach ([CacheMutationOutcome::Removed, CacheMutationOutcome::NotFound, CacheMutationOutcome::Conflict, CacheMutationOutcome::Rejected, CacheMutationOutcome::Unavailable] as $outcome) {
            self::assertNull(CacheAdapterMutationResult::withoutEvidence($outcome, $entry->keyIdentity())->entryEvidence());
        }
    }

    public function testNonStoredEvidenceIsRejected(): void
    {
        $this->expectException(InvalidCacheAdapterContractException::class);
        $entry = FakeCacheAdapter::entry();
        new CacheAdapterMutationResult(CacheMutationOutcome::Removed, $entry->keyIdentity(), FakeCacheAdapter::evidence($entry));
    }
}
