<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterMemory\Adapter;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheAdapterContract\Adapter\CacheAdapter;
use Kongono\CacheAdapterContract\Adapter\CacheAdapterIdentity;
use Kongono\CacheAdapterContract\Adapter\CacheAdapterLimits;
use Kongono\CacheAdapterContract\AdapterContractVersion;
use Kongono\CacheAdapterContract\Request\CacheAdapterLookupRequest;
use Kongono\CacheAdapterContract\Request\CacheAdapterRemoveRequest;
use Kongono\CacheAdapterContract\Request\CacheAdapterReplaceRequest;
use Kongono\CacheAdapterContract\Request\CacheAdapterStoreRequest;
use Kongono\CacheAdapterContract\Result\CacheAdapterLookupResult;
use Kongono\CacheAdapterContract\Result\CacheAdapterMutationResult;
use Kongono\CacheAdapterMemory\Configuration\MemoryAdapterCapacity;
use Kongono\CacheAdapterMemory\Configuration\MemoryEvictionPolicy;
use Kongono\CacheAdapterMemory\Internal\MemoryAdapterGuard;
use Kongono\CacheAdapterMemory\Internal\MemoryEntry;
use Kongono\CacheContract\Identity\CacheContentIdentity;
use Kongono\CacheContract\Identity\CacheKeyIdentity;
use Kongono\CacheContract\Outcome\CacheMutationOutcome;
use Kongono\CacheContract\Version\CacheEntryVersion;

/** Implements deterministic bounded process-local cache entry storage. */
#[Api(ApiStatus::Experimental)]
final class BoundedMemoryCacheAdapter implements CacheAdapter
{
    /** @var array<string, MemoryEntry> */
    private array $entries = [];
    private int $aggregateValueBytes = 0;
    private int $nextSequence = 1;

    public function __construct(
        private readonly MemoryAdapterCapacity $capacity,
        private readonly MemoryEvictionPolicy $evictionPolicy,
    ) {}

    public function protocolVersion(): AdapterContractVersion
    {
        return AdapterContractVersion::Version1;
    }

    public function identity(): CacheAdapterIdentity
    {
        return new CacheAdapterIdentity('bounded-memory', 1);
    }

    public function limits(): CacheAdapterLimits
    {
        return $this->capacity->limits();
    }

    public function lookup(CacheAdapterLookupRequest $request): CacheAdapterLookupResult
    {
        $key = $request->keyIdentity();
        $entry = $this->entries[$key->digest()] ?? null;

        return null === $entry
            ? CacheAdapterLookupResult::miss($this->copyKey($key))
            : CacheAdapterLookupResult::found($this->copyKey($key), $entry->evidence());
    }

    public function store(CacheAdapterStoreRequest $request): CacheAdapterMutationResult
    {
        $entry = $request->entry();
        $key = $entry->keyIdentity();
        $index = $key->digest();
        if (isset($this->entries[$index])) {
            return CacheAdapterMutationResult::withoutEvidence(CacheMutationOutcome::Conflict, $this->copyKey($key));
        }
        if (!$this->fitsIndividually($entry->valueBytes()->byteCount())) {
            return CacheAdapterMutationResult::withoutEvidence(CacheMutationOutcome::Rejected, $this->copyKey($key));
        }

        return $this->storeCandidate($entry, null);
    }

    public function replace(CacheAdapterReplaceRequest $request): CacheAdapterMutationResult
    {
        $entry = $request->entry();
        $key = $entry->keyIdentity();
        $index = $key->digest();
        $current = $this->entries[$index] ?? null;
        if (null === $current) {
            return CacheAdapterMutationResult::withoutEvidence(CacheMutationOutcome::NotFound, $this->copyKey($key));
        }
        if (!$current->versionEquals($request->expectedVersion())) {
            return CacheAdapterMutationResult::withoutEvidence(CacheMutationOutcome::Conflict, $this->copyKey($key));
        }
        if (!$this->fitsIndividually($entry->valueBytes()->byteCount())) {
            return CacheAdapterMutationResult::withoutEvidence(CacheMutationOutcome::Rejected, $this->copyKey($key));
        }

        return $this->storeCandidate($entry, $current);
    }

    public function remove(CacheAdapterRemoveRequest $request): CacheAdapterMutationResult
    {
        $key = $request->keyIdentity();
        $index = $key->digest();
        $current = $this->entries[$index] ?? null;
        if (null === $current) {
            return CacheAdapterMutationResult::withoutEvidence(CacheMutationOutcome::NotFound, $this->copyKey($key));
        }
        if (!$current->versionEquals($request->expectedVersion())) {
            return CacheAdapterMutationResult::withoutEvidence(CacheMutationOutcome::Conflict, $this->copyKey($key));
        }

        $candidateEntries = $this->entries;
        unset($candidateEntries[$index]);
        $candidateBytes = $this->aggregateValueBytes - $current->valueByteCount();
        if ($candidateBytes < 0) {
            throw MemoryAdapterGuard::impossibleState();
        }
        $this->entries = $candidateEntries;
        $this->aggregateValueBytes = $candidateBytes;

        return CacheAdapterMutationResult::withoutEvidence(CacheMutationOutcome::Removed, $this->copyKey($key));
    }

    private function storeCandidate(\Kongono\CacheAdapterContract\Adapter\CacheAdapterEntry $entry, ?MemoryEntry $replaced): CacheAdapterMutationResult
    {
        MemoryAdapterGuard::sequence($this->nextSequence);
        $key = $entry->keyIdentity();
        $index = $key->digest();
        $version = $this->versionFor($this->nextSequence, $key, $entry->contentIdentity());
        $candidateEntries = $this->entries;
        $candidateBytes = $this->aggregateValueBytes;
        if (null !== $replaced) {
            unset($candidateEntries[$index]);
            $candidateBytes -= $replaced->valueByteCount();
        }
        $candidate = new MemoryEntry($entry, $version);
        $candidateEntries[$index] = $candidate;
        $candidateBytes += $candidate->valueByteCount();
        $this->evictCandidate($candidateEntries, $candidateBytes);

        $this->entries = $candidateEntries;
        $this->aggregateValueBytes = $candidateBytes;
        ++$this->nextSequence;

        return CacheAdapterMutationResult::stored($this->copyKey($key), $candidate->evidence());
    }

    /** @param array<string, MemoryEntry> $entries */
    private function evictCandidate(array &$entries, int &$bytes): void
    {
        while (count($entries) > $this->capacity->maximumEntries() || $bytes > $this->capacity->maximumAggregateValueBytes()) {
            $oldest = match ($this->evictionPolicy) {
                MemoryEvictionPolicy::OldestInsertionFirst => array_key_first($entries),
            };
            if (null === $oldest) {
                throw MemoryAdapterGuard::impossibleState();
            }
            $bytes -= $entries[$oldest]->valueByteCount();
            unset($entries[$oldest]);
        }
        if ($bytes < 0) {
            throw MemoryAdapterGuard::impossibleState();
        }
    }

    private function fitsIndividually(int $byteCount): bool
    {
        return $byteCount <= $this->capacity->maximumValueBytes()
            && $byteCount <= $this->capacity->maximumAggregateValueBytes();
    }

    private function versionFor(int $sequence, CacheKeyIdentity $key, CacheContentIdentity $content): CacheEntryVersion
    {
        return new CacheEntryVersion(hash('sha256', "kongono.cache.adapter.memory.entry-version.v1\0" . $sequence . "\0" . $key->digest() . "\0" . $content->digest()));
    }

    private function copyKey(CacheKeyIdentity $key): CacheKeyIdentity
    {
        return new CacheKeyIdentity($key->digest());
    }
}
