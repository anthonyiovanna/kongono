<?php

declare(strict_types=1);

namespace Kongono\CacheRuntime\Tests\Integration;

use Kongono\CacheAdapterContract\Adapter\CacheAdapterIdentity;
use Kongono\CacheAdapterContract\Request\CacheAdapterRemoveRequest;
use Kongono\CacheAdapterContract\Request\CacheAdapterReplaceRequest;
use Kongono\CacheAdapterContract\Request\CacheAdapterStoreRequest;
use Kongono\CacheAdapterContract\Result\CacheAdapterMutationResult;
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
use Kongono\CacheContract\Outcome\CacheMutationOutcome;
use Kongono\CacheContract\Time\CacheInstant;
use Kongono\CacheContract\Value\CacheValueBytes;
use Kongono\CacheContract\Version\CacheEntryVersion;
use Kongono\CacheRuntime\Binding\CacheRuntimeBinding;
use Kongono\CacheRuntime\Exception\InvalidCacheRuntimeException;
use Kongono\CacheRuntime\Operation\CacheRemoveRequest as RuntimeRemoveRequest;
use Kongono\CacheRuntime\Operation\CacheReplaceRequest as RuntimeReplaceRequest;
use Kongono\CacheRuntime\Operation\CacheStoreRequest as RuntimeStoreRequest;
use Kongono\CacheRuntime\Runtime\ExactCurrentCacheRuntime;
use Kongono\CacheRuntime\Tests\Fixture\ScriptedCacheAdapter;
use PHPUnit\Framework\TestCase;

final class CacheRuntimeMutationTest extends TestCase
{
    public function testValueLimitEqualityAndStoredEvidenceAreExactWhilePlusOneStopsBeforeInvocation(): void
    {
        $adapter = (new ScriptedCacheAdapter())->queueMutation('store', static fn(CacheAdapterStoreRequest $request): CacheAdapterMutationResult => ScriptedCacheAdapter::stored($request->entry()));
        $runtime = self::runtime($adapter);
        $stored = $runtime->store(new RuntimeStoreRequest(new LogicalCacheKey('item'), new CacheValueBytes('data'), new CacheInstant(10)));
        self::assertSame(CacheMutationOutcome::Stored, $stored->outcome());
        self::assertSame('data', $stored->entryEvidence()?->valueBytes()->bytes());
        try {
            $runtime->store(new RuntimeStoreRequest(new LogicalCacheKey('other'), new CacheValueBytes('extra'), new CacheInstant(10)));
            self::fail('Expected profile value-limit rejection.');
        } catch (InvalidCacheRuntimeException $failure) {
            self::assertSame('KGN-CACHE-RUNTIME-2032', $failure->diagnosticId());
        }
        self::assertSame(['store'], $adapter->invocations());
    }

    public function testLifetimeOverflowStopsBeforeInvocation(): void
    {
        $adapter = new ScriptedCacheAdapter();
        try {
            self::runtime($adapter)->store(new RuntimeStoreRequest(new LogicalCacheKey('item'), new CacheValueBytes('data'), new CacheInstant(253_402_300_799_999_990)));
            self::fail('Expected instant overflow.');
        } catch (InvalidCacheRuntimeException $failure) {
            self::assertSame('KGN-CACHE-RUNTIME-2032', $failure->diagnosticId());
        }
        self::assertSame([], $adapter->invocations());
    }

    public function testAllowedMutationOutcomesAndExpectedVersionsArePreserved(): void
    {
        $version = new CacheEntryVersion(str_repeat('a', 64));
        $adapter = (new ScriptedCacheAdapter())
            ->queueMutation('store', static fn(CacheAdapterStoreRequest $request): CacheAdapterMutationResult => CacheAdapterMutationResult::withoutEvidence(CacheMutationOutcome::Conflict, $request->entry()->keyIdentity()))
            ->queueMutation('replace', static fn(CacheAdapterReplaceRequest $request): CacheAdapterMutationResult => CacheAdapterMutationResult::withoutEvidence(CacheMutationOutcome::NotFound, $request->entry()->keyIdentity()))
            ->queueMutation('replace', static fn(CacheAdapterReplaceRequest $request): CacheAdapterMutationResult => CacheAdapterMutationResult::withoutEvidence(CacheMutationOutcome::Conflict, $request->entry()->keyIdentity()))
            ->queueMutation('replace', static fn(CacheAdapterReplaceRequest $request): CacheAdapterMutationResult => CacheAdapterMutationResult::withoutEvidence(CacheMutationOutcome::Rejected, $request->entry()->keyIdentity()))
            ->queueMutation('replace', static fn(CacheAdapterReplaceRequest $request): CacheAdapterMutationResult => CacheAdapterMutationResult::withoutEvidence(CacheMutationOutcome::Unavailable, $request->entry()->keyIdentity()))
            ->queueMutation('remove', static fn(CacheAdapterRemoveRequest $request): CacheAdapterMutationResult => CacheAdapterMutationResult::withoutEvidence(CacheMutationOutcome::Removed, $request->keyIdentity()))
            ->queueMutation('remove', static fn(CacheAdapterRemoveRequest $request): CacheAdapterMutationResult => CacheAdapterMutationResult::withoutEvidence(CacheMutationOutcome::NotFound, $request->keyIdentity()))
            ->queueMutation('remove', static fn(CacheAdapterRemoveRequest $request): CacheAdapterMutationResult => CacheAdapterMutationResult::withoutEvidence(CacheMutationOutcome::Conflict, $request->keyIdentity()))
            ->queueMutation('remove', static fn(CacheAdapterRemoveRequest $request): CacheAdapterMutationResult => CacheAdapterMutationResult::withoutEvidence(CacheMutationOutcome::Rejected, $request->keyIdentity()))
            ->queueMutation('remove', static fn(CacheAdapterRemoveRequest $request): CacheAdapterMutationResult => CacheAdapterMutationResult::withoutEvidence(CacheMutationOutcome::Unavailable, $request->keyIdentity()));
        $runtime = self::runtime($adapter);
        self::assertSame(CacheMutationOutcome::Conflict, $runtime->store(new RuntimeStoreRequest(new LogicalCacheKey('item'), new CacheValueBytes('data'), new CacheInstant(10)))->outcome());
        foreach ([CacheMutationOutcome::NotFound, CacheMutationOutcome::Conflict, CacheMutationOutcome::Rejected, CacheMutationOutcome::Unavailable] as $expected) {
            self::assertSame($expected, $runtime->replace(new RuntimeReplaceRequest(new LogicalCacheKey('item'), new CacheValueBytes('data'), $version, new CacheInstant(10)))->outcome());
        }
        foreach ([CacheMutationOutcome::Removed, CacheMutationOutcome::NotFound, CacheMutationOutcome::Conflict, CacheMutationOutcome::Rejected, CacheMutationOutcome::Unavailable] as $expected) {
            self::assertSame($expected, $runtime->remove(new RuntimeRemoveRequest(new LogicalCacheKey('item'), $version, new CacheInstant(10)))->outcome());
        }
        foreach ($adapter->requests() as $request) {
            if ($request instanceof CacheAdapterReplaceRequest || $request instanceof CacheAdapterRemoveRequest) {
                self::assertSame($version, $request->expectedVersion());
            }
        }
    }

    private static function runtime(ScriptedCacheAdapter $adapter): ExactCurrentCacheRuntime
    {
        $set = new CacheProfileSet([new CacheProfileDeclaration(ContractVersion::Version1, new CacheProfileName('primary'), new CacheNamespace('runtime-tests'), new CacheCodecIdentity('text', 1), new CacheLifetime(10), new CacheProfileValueLimit(4))]);

        return new ExactCurrentCacheRuntime(new CacheRuntimeBinding($set, (new CacheProfileBuilder())->build($set)->artifact()->bytes(), new CacheProfileName('primary'), new CacheAdapterIdentity('scripted', 1), $adapter));
    }
}
