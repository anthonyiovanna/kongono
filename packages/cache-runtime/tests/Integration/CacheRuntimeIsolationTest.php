<?php

declare(strict_types=1);

namespace Kongono\CacheRuntime\Tests\Integration;

use Error;
use Fiber;
use Kongono\CacheAdapterContract\Adapter\CacheAdapterIdentity;
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
use Kongono\CacheContract\Time\CacheInstant;
use Kongono\CacheRuntime\Binding\CacheRuntimeBinding;
use Kongono\CacheRuntime\Exception\CacheRuntimeOperationException;
use Kongono\CacheRuntime\Operation\CacheLookupRequest;
use Kongono\CacheRuntime\Runtime\ExactCurrentCacheRuntime;
use Kongono\CacheRuntime\Tests\Fixture\ScriptedCacheAdapter;
use PHPUnit\Framework\TestCase;

final class CacheRuntimeIsolationTest extends TestCase
{
    public function testSameInstanceReentrancyIsRejectedWithOneInvocationAndThenRetires(): void
    {
        $adapter = new ScriptedCacheAdapter();
        $runtime = self::runtime($adapter);
        $adapter->reenterOnce(static function () use ($runtime): void {
            $runtime->lookup(self::request());
        });
        try {
            $runtime->lookup(self::request());
            self::fail('Expected reentrant operation rejection.');
        } catch (CacheRuntimeOperationException $failure) {
            self::assertSame('KGN-CACHE-RUNTIME-2035', $failure->diagnosticId());
        }
        self::assertSame(['lookup'], $adapter->invocations());
        self::assertSame(CacheLookupOutcome::Miss, $runtime->lookup(self::request())->outcome());
        self::assertSame(['lookup', 'lookup'], $adapter->invocations());
    }

    public function testSeparateRuntimesSharingOneAdapterOverlapWithoutGuardInterference(): void
    {
        $adapter = (new ScriptedCacheAdapter())->reenterOnce(static function (): void {
            Fiber::suspend('first-active');
        });
        $left = self::runtime($adapter);
        $right = self::runtime($adapter);
        $first = new Fiber(static fn() => $left->lookup(self::request())->outcome());
        $second = new Fiber(static fn() => $right->lookup(self::request())->outcome());
        self::assertSame('first-active', $first->start());
        $second->start();
        $first->resume();
        self::assertSame(CacheLookupOutcome::Miss, $first->getReturn());
        self::assertSame(CacheLookupOutcome::Miss, $second->getReturn());
        self::assertSame(['lookup', 'lookup'], $adapter->invocations());
    }

    public function testRepeatedSuccessAndErrorPathsRetainNoActiveOperation(): void
    {
        $adapter = (new ScriptedCacheAdapter())->queueLookup(new Error('native'));
        $runtime = self::runtime($adapter);
        try {
            $runtime->lookup(self::request());
        } catch (Error) {
            self::addToAssertionCount(1);
        }
        for ($iteration = 0; $iteration < 8; ++$iteration) {
            self::assertSame(CacheLookupOutcome::Miss, $runtime->lookup(self::request())->outcome());
        }
    }

    private static function runtime(ScriptedCacheAdapter $adapter): ExactCurrentCacheRuntime
    {
        $set = new CacheProfileSet([new CacheProfileDeclaration(ContractVersion::Version1, new CacheProfileName('primary'), new CacheNamespace('runtime-tests'), new CacheCodecIdentity('text', 1), new CacheLifetime(10), new CacheProfileValueLimit(4))]);

        return new ExactCurrentCacheRuntime(new CacheRuntimeBinding($set, (new CacheProfileBuilder())->build($set)->artifact()->bytes(), new CacheProfileName('primary'), new CacheAdapterIdentity('scripted', 1), $adapter));
    }

    private static function request(): CacheLookupRequest
    {
        return new CacheLookupRequest(new LogicalCacheKey('item'), new CacheInstant(10));
    }
}
