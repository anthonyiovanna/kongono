<?php

declare(strict_types=1);

namespace Kongono\CacheRuntime\Tests\Integration;

use Error;
use Kongono\CacheAdapterContract\Adapter\CacheAdapterEntry;
use Kongono\CacheAdapterContract\Adapter\CacheAdapterIdentity;
use Kongono\CacheAdapterContract\Request\CacheAdapterStoreRequest;
use Kongono\CacheAdapterContract\Result\CacheAdapterLookupResult;
use Kongono\CacheAdapterContract\Result\CacheAdapterMutationResult;
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
use Kongono\CacheContract\Outcome\CacheMutationOutcome;
use Kongono\CacheContract\Time\CacheInstant;
use Kongono\CacheContract\Value\CacheValueBytes;
use Kongono\CacheRuntime\Binding\CacheRuntimeBinding;
use Kongono\CacheRuntime\Exception\CacheRuntimeOperationException;
use Kongono\CacheRuntime\Exception\InvalidCacheRuntimeException;
use Kongono\CacheRuntime\Operation\CacheLookupRequest;
use Kongono\CacheRuntime\Operation\CacheStoreRequest;
use Kongono\CacheRuntime\Runtime\ExactCurrentCacheRuntime;
use Kongono\CacheRuntime\Tests\Fixture\ScriptedCacheAdapter;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class CacheRuntimeFailureTest extends TestCase
{
    public function testMismatchedKeyCodecValueFreshnessCandidateAndOutcomeFailClosed(): void
    {
        $requested = self::key('item');
        $other = self::key('other');
        $cases = [
            (new ScriptedCacheAdapter())->queueLookup(CacheAdapterLookupResult::miss($other)),
            (new ScriptedCacheAdapter())->queueLookup(CacheAdapterLookupResult::found($requested, ScriptedCacheAdapter::evidence(self::entry($requested, 'other', 'data', 10, 20)))),
            (new ScriptedCacheAdapter())->queueLookup(CacheAdapterLookupResult::found($requested, ScriptedCacheAdapter::evidence(self::entry($requested, 'text', 'extra', 10, 20)))),
            (new ScriptedCacheAdapter())->queueLookup(CacheAdapterLookupResult::found($requested, ScriptedCacheAdapter::evidence(self::entry($requested, 'text', 'data', 10, 21)))),
        ];
        foreach ($cases as $adapter) {
            try {
                self::runtime($adapter)->lookup(new CacheLookupRequest(new LogicalCacheKey('item'), new CacheInstant(15)));
                self::fail('Expected invalid adapter evidence.');
            } catch (InvalidCacheRuntimeException $failure) {
                self::assertSame('KGN-CACHE-RUNTIME-2033', $failure->diagnosticId());
            }
        }

        $candidateMismatch = (new ScriptedCacheAdapter())->queueMutation('store', static function (CacheAdapterStoreRequest $request): CacheAdapterMutationResult {
            $different = self::entry($request->entry()->keyIdentity(), 'text', 'diff', 10, 20);

            return ScriptedCacheAdapter::stored($different);
        });
        $impossible = (new ScriptedCacheAdapter())->queueMutation('store', static fn(CacheAdapterStoreRequest $request): CacheAdapterMutationResult => CacheAdapterMutationResult::withoutEvidence(CacheMutationOutcome::Removed, $request->entry()->keyIdentity()));
        foreach ([$candidateMismatch, $impossible] as $adapter) {
            try {
                self::runtime($adapter)->store(new CacheStoreRequest(new LogicalCacheKey('item'), new CacheValueBytes('data'), new CacheInstant(10)));
                self::fail('Expected invalid mutation evidence.');
            } catch (InvalidCacheRuntimeException $failure) {
                self::assertSame('KGN-CACHE-RUNTIME-2033', $failure->diagnosticId());
            }
        }
    }

    public function testAdapterThrowableIsTranslatedOnceButErrorIsNotAndGuardRetires(): void
    {
        $cause = new RuntimeException('private-provider-message');
        $adapter = (new ScriptedCacheAdapter())->queueLookup($cause);
        $runtime = self::runtime($adapter);
        try {
            $runtime->lookup(new CacheLookupRequest(new LogicalCacheKey('item'), new CacheInstant(10)));
            self::fail('Expected adapter failure.');
        } catch (CacheRuntimeOperationException $failure) {
            self::assertSame('KGN-CACHE-RUNTIME-2034', $failure->diagnosticId());
            self::assertSame($cause, $failure->getPrevious());
        }
        self::assertSame(CacheLookupOutcome::Miss, $runtime->lookup(new CacheLookupRequest(new LogicalCacheKey('item'), new CacheInstant(10)))->outcome());

        $error = new Error('native-error');
        $errorAdapter = (new ScriptedCacheAdapter())->queueLookup($error);
        $errorRuntime = self::runtime($errorAdapter);
        try {
            $errorRuntime->lookup(new CacheLookupRequest(new LogicalCacheKey('item'), new CacheInstant(10)));
            self::fail('Expected native Error.');
        } catch (Error $failure) {
            self::assertSame($error, $failure);
        }
        self::assertSame(CacheLookupOutcome::Miss, $errorRuntime->lookup(new CacheLookupRequest(new LogicalCacheKey('item'), new CacheInstant(10)))->outcome());
    }

    private static function runtime(ScriptedCacheAdapter $adapter): ExactCurrentCacheRuntime
    {
        $set = new CacheProfileSet([new CacheProfileDeclaration(ContractVersion::Version1, new CacheProfileName('primary'), new CacheNamespace('runtime-tests'), new CacheCodecIdentity('text', 1), new CacheLifetime(10), new CacheProfileValueLimit(4))]);

        return new ExactCurrentCacheRuntime(new CacheRuntimeBinding($set, (new CacheProfileBuilder())->build($set)->artifact()->bytes(), new CacheProfileName('primary'), new CacheAdapterIdentity('scripted', 1), $adapter));
    }

    private static function key(string $key): CacheKeyIdentity
    {
        return CacheKeyIdentity::from(new CacheNamespace('runtime-tests'), new LogicalCacheKey($key));
    }

    private static function entry(CacheKeyIdentity $key, string $codec, string $bytes, int $stored, int $expires): CacheAdapterEntry
    {
        $value = new CacheValueBytes($bytes);

        return new CacheAdapterEntry(ContractVersion::Version1, $key, new CacheCodecIdentity($codec, 1), $value, $value->contentIdentity(), new CacheFreshnessWindow(new CacheInstant($stored), new CacheInstant($expires)));
    }
}
