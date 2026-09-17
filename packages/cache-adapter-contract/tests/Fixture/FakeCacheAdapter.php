<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterContract\Tests\Fixture;

use Kongono\CacheAdapterContract\Adapter\CacheAdapter;
use Kongono\CacheAdapterContract\Adapter\CacheAdapterEntry;
use Kongono\CacheAdapterContract\Adapter\CacheAdapterIdentity;
use Kongono\CacheAdapterContract\Adapter\CacheAdapterLimits;
use Kongono\CacheAdapterContract\AdapterContractVersion;
use Kongono\CacheAdapterContract\Request\CacheAdapterLookupRequest;
use Kongono\CacheAdapterContract\Request\CacheAdapterRemoveRequest;
use Kongono\CacheAdapterContract\Request\CacheAdapterReplaceRequest;
use Kongono\CacheAdapterContract\Request\CacheAdapterStoreRequest;
use Kongono\CacheAdapterContract\Result\CacheAdapterLookupResult;
use Kongono\CacheAdapterContract\Result\CacheAdapterMutationResult;
use Kongono\CacheContract\Codec\CacheCodecIdentity;
use Kongono\CacheContract\ContractVersion;
use Kongono\CacheContract\Entry\CacheEntryEvidence;
use Kongono\CacheContract\Freshness\CacheFreshnessWindow;
use Kongono\CacheContract\Identity\CacheContentIdentity;
use Kongono\CacheContract\Identity\CacheKeyIdentity;
use Kongono\CacheContract\Identity\CacheNamespace;
use Kongono\CacheContract\Identity\LogicalCacheKey;
use Kongono\CacheContract\Outcome\CacheMutationOutcome;
use Kongono\CacheContract\Time\CacheInstant;
use Kongono\CacheContract\Value\CacheValueBytes;
use Kongono\CacheContract\Version\CacheEntryVersion;

final class FakeCacheAdapter implements CacheAdapter
{
    public function protocolVersion(): AdapterContractVersion
    {
        return AdapterContractVersion::Version1;
    }

    public function identity(): CacheAdapterIdentity
    {
        return new CacheAdapterIdentity('fake', 1);
    }

    public function limits(): CacheAdapterLimits
    {
        return new CacheAdapterLimits(1, 16, 16);
    }

    public function lookup(CacheAdapterLookupRequest $request): CacheAdapterLookupResult
    {
        return CacheAdapterLookupResult::miss($request->keyIdentity());
    }

    public function store(CacheAdapterStoreRequest $request): CacheAdapterMutationResult
    {
        return CacheAdapterMutationResult::stored($request->entry()->keyIdentity(), self::evidence($request->entry()));
    }

    public function replace(CacheAdapterReplaceRequest $request): CacheAdapterMutationResult
    {
        return CacheAdapterMutationResult::withoutEvidence(CacheMutationOutcome::Conflict, $request->entry()->keyIdentity());
    }

    public function remove(CacheAdapterRemoveRequest $request): CacheAdapterMutationResult
    {
        return CacheAdapterMutationResult::withoutEvidence(CacheMutationOutcome::NotFound, $request->keyIdentity());
    }

    public static function key(string $value = 'item'): CacheKeyIdentity
    {
        return CacheKeyIdentity::from(new CacheNamespace('tests'), new LogicalCacheKey($value));
    }

    public static function entry(string $value = 'item', string $bytes = 'value'): CacheAdapterEntry
    {
        $payload = new CacheValueBytes($bytes);

        return new CacheAdapterEntry(
            ContractVersion::Version1,
            self::key($value),
            new CacheCodecIdentity('text', 1),
            $payload,
            $payload->contentIdentity(),
            new CacheFreshnessWindow(new CacheInstant(10), new CacheInstant(20)),
        );
    }

    public static function evidence(CacheAdapterEntry $entry): CacheEntryEvidence
    {
        return new CacheEntryEvidence(
            $entry->contractVersion(),
            $entry->keyIdentity(),
            $entry->codecIdentity(),
            $entry->valueBytes(),
            new CacheContentIdentity($entry->contentIdentity()->digest()),
            $entry->freshnessWindow(),
            new CacheEntryVersion(hash('sha256', 'fake-version')),
        );
    }
}
