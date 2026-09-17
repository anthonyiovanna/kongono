<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterContract\Tests\Unit\Request;

use Kongono\CacheAdapterContract\Request\CacheAdapterLookupRequest;
use Kongono\CacheAdapterContract\Request\CacheAdapterRemoveRequest;
use Kongono\CacheAdapterContract\Request\CacheAdapterReplaceRequest;
use Kongono\CacheAdapterContract\Request\CacheAdapterStoreRequest;
use Kongono\CacheAdapterContract\Tests\Fixture\FakeCacheAdapter;
use PHPUnit\Framework\TestCase;

final class CacheAdapterRequestTest extends TestCase
{
    public function testRequestsCarryOnlyCompleteImmutableInputs(): void
    {
        $entry = FakeCacheAdapter::entry();
        $evidence = FakeCacheAdapter::evidence($entry);
        self::assertTrue((new CacheAdapterLookupRequest($entry->keyIdentity()))->keyIdentity()->equals($entry->keyIdentity()));
        self::assertSame($entry, (new CacheAdapterStoreRequest($entry))->entry());
        self::assertTrue((new CacheAdapterReplaceRequest($entry, $evidence->entryVersion()))->expectedVersion()->equals($evidence->entryVersion()));
        self::assertTrue((new CacheAdapterRemoveRequest($entry->keyIdentity(), $evidence->entryVersion()))->expectedVersion()->equals($evidence->entryVersion()));
    }
}
