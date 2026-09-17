<?php

declare(strict_types=1);

namespace Kongono\CacheRuntime\Tests\Unit\Runtime;

use Kongono\CacheAdapterContract\Adapter\CacheAdapterIdentity;
use Kongono\CacheAdapterContract\Request\CacheAdapterLookupRequest;
use Kongono\CacheAdapterContract\Request\CacheAdapterRemoveRequest;
use Kongono\CacheAdapterContract\Request\CacheAdapterReplaceRequest;
use Kongono\CacheAdapterContract\Request\CacheAdapterStoreRequest;
use Kongono\CacheBuild\Build\CacheProfileBuilder;
use Kongono\CacheBuild\Profile\CacheProfileDeclaration;
use Kongono\CacheBuild\Profile\CacheProfileName;
use Kongono\CacheBuild\Profile\CacheProfileSet;
use Kongono\CacheBuild\Profile\CacheProfileValueLimit;
use Kongono\CacheContract\Codec\CacheCodecIdentity;
use Kongono\CacheContract\ContractVersion;
use Kongono\CacheContract\Freshness\CacheLifetime;
use Kongono\CacheContract\Identity\CacheKeyIdentity;
use Kongono\CacheContract\Identity\CacheNamespace;
use Kongono\CacheContract\Identity\LogicalCacheKey;
use Kongono\CacheContract\Time\CacheInstant;
use Kongono\CacheContract\Value\CacheValueBytes;
use Kongono\CacheContract\Version\CacheEntryVersion;
use Kongono\CacheRuntime\Binding\CacheRuntimeBinding;
use Kongono\CacheRuntime\Operation\CacheLookupRequest as RuntimeLookupRequest;
use Kongono\CacheRuntime\Operation\CacheRemoveRequest as RuntimeRemoveRequest;
use Kongono\CacheRuntime\Operation\CacheReplaceRequest as RuntimeReplaceRequest;
use Kongono\CacheRuntime\Operation\CacheStoreRequest as RuntimeStoreRequest;
use Kongono\CacheRuntime\Runtime\ExactCurrentCacheRuntime;
use Kongono\CacheRuntime\Tests\Fixture\ScriptedCacheAdapter;
use PHPUnit\Framework\TestCase;

final class ExactCurrentCacheRuntimeTest extends TestCase
{
    public function testEachPublicOperationDerivesTheExactKeyAndCallsOnlyItsAdapterMethod(): void
    {
        $adapter = new ScriptedCacheAdapter();
        $version = new CacheEntryVersion(str_repeat('a', 64));
        $runtime = self::runtime($adapter);
        $runtime->lookup(new RuntimeLookupRequest(new LogicalCacheKey('item'), new CacheInstant(10)));
        $runtime->store(new RuntimeStoreRequest(new LogicalCacheKey('item'), new CacheValueBytes('data'), new CacheInstant(10)));
        $runtime->replace(new RuntimeReplaceRequest(new LogicalCacheKey('item'), new CacheValueBytes('data'), $version, new CacheInstant(10)));
        $runtime->remove(new RuntimeRemoveRequest(new LogicalCacheKey('item'), $version, new CacheInstant(10)));
        self::assertSame(['lookup', 'store', 'replace', 'remove'], $adapter->invocations());
        $expected = CacheKeyIdentity::from(new CacheNamespace('runtime-tests'), new LogicalCacheKey('item'));
        foreach ($adapter->requests() as $request) {
            if ($request instanceof CacheAdapterLookupRequest || $request instanceof CacheAdapterRemoveRequest) {
                $key = $request->keyIdentity();
            } elseif ($request instanceof CacheAdapterStoreRequest || $request instanceof CacheAdapterReplaceRequest) {
                $key = $request->entry()->keyIdentity();
            } else {
                self::fail('Unexpected cache adapter request type.');
            }
            self::assertTrue($expected->equals($key));
        }
        $replaceRequest = $adapter->requests()[2];
        $removeRequest = $adapter->requests()[3];
        self::assertInstanceOf(CacheAdapterReplaceRequest::class, $replaceRequest);
        self::assertInstanceOf(CacheAdapterRemoveRequest::class, $removeRequest);
        self::assertSame($version, $replaceRequest->expectedVersion());
        self::assertSame($version, $removeRequest->expectedVersion());
    }

    private static function runtime(ScriptedCacheAdapter $adapter): ExactCurrentCacheRuntime
    {
        $set = new CacheProfileSet([new CacheProfileDeclaration(ContractVersion::Version1, new CacheProfileName('primary'), new CacheNamespace('runtime-tests'), new CacheCodecIdentity('text', 1), new CacheLifetime(10), new CacheProfileValueLimit(4))]);
        $bytes = (new CacheProfileBuilder())->build($set)->artifact()->bytes();

        return new ExactCurrentCacheRuntime(new CacheRuntimeBinding($set, $bytes, new CacheProfileName('primary'), new CacheAdapterIdentity('scripted', 1), $adapter));
    }
}
