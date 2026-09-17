<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterContract\Tests\Integration;

use Kongono\CacheAdapterContract\Adapter\CacheAdapter;
use Kongono\CacheAdapterContract\Request\CacheAdapterLookupRequest;
use Kongono\CacheAdapterContract\Request\CacheAdapterStoreRequest;
use Kongono\CacheAdapterContract\Result\CacheAdapterLookupStatus;
use Kongono\CacheAdapterContract\Tests\Fixture\FakeCacheAdapter;
use Kongono\CacheContract\Outcome\CacheMutationOutcome;
use PHPUnit\Framework\TestCase;

final class CacheAdapterProtocolTest extends TestCase
{
    public function testFakeAdapterIsDirectlySubstitutableWithoutDiscovery(): void
    {
        $adapter = new FakeCacheAdapter();
        self::assertInstanceOf(CacheAdapter::class, $adapter);
        self::assertSame(CacheAdapterLookupStatus::Miss, $adapter->lookup(new CacheAdapterLookupRequest(FakeCacheAdapter::key()))->status());
        self::assertSame(CacheMutationOutcome::Stored, $adapter->store(new CacheAdapterStoreRequest(FakeCacheAdapter::entry()))->outcome());
    }
}
