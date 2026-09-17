<?php

declare(strict_types=1);

namespace Kongono\CacheRuntime\Tests\Unit\Operation;

use Kongono\CacheContract\Identity\LogicalCacheKey;
use Kongono\CacheContract\Time\CacheInstant;
use Kongono\CacheContract\Value\CacheValueBytes;
use Kongono\CacheContract\Version\CacheEntryVersion;
use Kongono\CacheRuntime\Operation\CacheLookupRequest;
use Kongono\CacheRuntime\Operation\CacheRemoveRequest;
use Kongono\CacheRuntime\Operation\CacheReplaceRequest;
use Kongono\CacheRuntime\Operation\CacheStoreRequest;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class CacheRuntimeRequestTest extends TestCase
{
    public function testRequestsAreReadonlyAndPreserveExplicitValues(): void
    {
        $key = new LogicalCacheKey('item');
        $value = new CacheValueBytes('data');
        $version = new CacheEntryVersion(str_repeat('a', 64));
        $now = new CacheInstant(10);
        $requests = [new CacheLookupRequest($key, $now), new CacheStoreRequest($key, $value, $now), new CacheReplaceRequest($key, $value, $version, $now), new CacheRemoveRequest($key, $version, $now)];
        foreach ($requests as $request) {
            self::assertTrue((new ReflectionClass($request))->isReadOnly());
            self::assertSame($key, $request->key());
            self::assertSame($now, $request->now());
        }
        self::assertSame($value, $requests[1]->value());
        self::assertSame($value, $requests[2]->value());
        self::assertSame($version, $requests[2]->expectedVersion());
        self::assertSame($version, $requests[3]->expectedVersion());
    }
}
