<?php

declare(strict_types=1);

namespace Kongono\CacheRuntime\Tests\Security;

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
use Kongono\CacheContract\Time\CacheInstant;
use Kongono\CacheRuntime\Binding\CacheRuntimeBinding;
use Kongono\CacheRuntime\Exception\CacheRuntimeOperationException;
use Kongono\CacheRuntime\Operation\CacheLookupRequest;
use Kongono\CacheRuntime\Runtime\ExactCurrentCacheRuntime;
use Kongono\CacheRuntime\Tests\Fixture\ScriptedCacheAdapter;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class CacheRuntimeDisclosureTest extends TestCase
{
    public function testPrivateBindingAndProviderMaterialNeverReachPresentation(): void
    {
        $private = ['private-profile', 'private-namespace', 'private-adapter', 'private-value', 'private-version', 'private-digest', '1700000000000000', 'private-artifact-fragment', 'private-provider-message'];
        $set = new CacheProfileSet([new CacheProfileDeclaration(ContractVersion::Version1, new CacheProfileName('private-profile'), new CacheNamespace('private-namespace'), new CacheCodecIdentity('text', 1), new CacheLifetime(10), new CacheProfileValueLimit(32))]);
        $bytes = (new CacheProfileBuilder())->build($set)->artifact()->bytes();
        $adapter = (new ScriptedCacheAdapter(new CacheAdapterIdentity('private-adapter', 1)))->queueLookup(new RuntimeException('private-provider-message'));
        $runtime = new ExactCurrentCacheRuntime(new CacheRuntimeBinding($set, $bytes, new CacheProfileName('private-profile'), new CacheAdapterIdentity('private-adapter', 1), $adapter));
        try {
            $runtime->lookup(new CacheLookupRequest(new LogicalCacheKey('private-value'), new CacheInstant(1_700_000_000_000_000)));
            self::fail('Expected adapter failure.');
        } catch (CacheRuntimeOperationException $failure) {
            foreach ($private as $value) {
                self::assertStringNotContainsString($value, $failure->getMessage());
                self::assertStringNotContainsString($value, (string) $failure);
                foreach ($failure->diagnosticContext()->fields() as $field) {
                    self::assertStringNotContainsString($value, (string) $field->value());
                }
            }
        }
    }
}
