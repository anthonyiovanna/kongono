<?php

declare(strict_types=1);

namespace Kongono\CacheRuntime\Tests\Unit\Binding;

use Kongono\CacheAdapterContract\Adapter\CacheAdapterIdentity;
use Kongono\CacheAdapterContract\Adapter\CacheAdapterLimits;
use Kongono\CacheBuild\Build\CacheProfileBuilder;
use Kongono\CacheBuild\Profile\CacheProfileDeclaration;
use Kongono\CacheBuild\Profile\CacheProfileName;
use Kongono\CacheBuild\Profile\CacheProfileSet;
use Kongono\CacheBuild\Profile\CacheProfileValueLimit;
use Kongono\CacheContract\Codec\CacheCodecIdentity;
use Kongono\CacheContract\ContractVersion;
use Kongono\CacheContract\Freshness\CacheLifetime;
use Kongono\CacheContract\Identity\CacheNamespace;
use Kongono\CacheRuntime\Binding\CacheRuntimeBinding;
use Kongono\CacheRuntime\Exception\InvalidCacheRuntimeException;
use Kongono\CacheRuntime\Tests\Fixture\ScriptedCacheAdapter;
use PHPUnit\Framework\TestCase;

final class CacheRuntimeBindingTest extends TestCase
{
    public function testExactCurrentSelectedProfileAndMatchingAdapterAreAccepted(): void
    {
        $set = self::set();
        $adapter = new ScriptedCacheAdapter();
        $binding = new CacheRuntimeBinding($set, self::artifact($set), new CacheProfileName('primary'), new CacheAdapterIdentity('scripted', 1), $adapter);
        self::assertSame('primary', $binding->profile()->name()->value());
        self::assertSame($adapter, $binding->adapter());
    }

    public function testStaleNoncanonicalMissingIdentityAndLimitFailuresAreTyped(): void
    {
        $set = self::set();
        $bytes = self::artifact($set);
        $cases = [
            fn() => new CacheRuntimeBinding(self::set(11), $bytes, new CacheProfileName('primary'), new CacheAdapterIdentity('scripted', 1), new ScriptedCacheAdapter()),
            fn() => new CacheRuntimeBinding($set, $bytes . "\n", new CacheProfileName('primary'), new CacheAdapterIdentity('scripted', 1), new ScriptedCacheAdapter()),
            fn() => new CacheRuntimeBinding($set, $bytes, new CacheProfileName('missing'), new CacheAdapterIdentity('scripted', 1), new ScriptedCacheAdapter()),
            fn() => new CacheRuntimeBinding($set, $bytes, new CacheProfileName('primary'), new CacheAdapterIdentity('other', 1), new ScriptedCacheAdapter()),
            fn() => new CacheRuntimeBinding($set, $bytes, new CacheProfileName('primary'), new CacheAdapterIdentity('scripted', 1), new ScriptedCacheAdapter(adapterLimits: new CacheAdapterLimits(1, 3, 3))),
        ];
        foreach ($cases as $case) {
            try {
                $case();
                self::fail('Expected binding failure.');
            } catch (InvalidCacheRuntimeException $failure) {
                self::assertSame('KGN-CACHE-RUNTIME-2031', $failure->diagnosticId());
            }
        }
    }

    private static function set(int $lifetime = 10): CacheProfileSet
    {
        return new CacheProfileSet([new CacheProfileDeclaration(
            ContractVersion::Version1,
            new CacheProfileName('primary'),
            new CacheNamespace('runtime-tests'),
            new CacheCodecIdentity('text', 1),
            new CacheLifetime($lifetime),
            new CacheProfileValueLimit(4),
        )]);
    }

    private static function artifact(CacheProfileSet $set): string
    {
        return (new CacheProfileBuilder())->build($set)->artifact()->bytes();
    }
}
