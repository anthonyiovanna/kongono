<?php

declare(strict_types=1);

namespace Kongono\CacheRuntime\Result;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheContract\Entry\CacheEntryEvidence;
use Kongono\CacheContract\Identity\CacheKeyIdentity;
use Kongono\CacheContract\Outcome\CacheLookupOutcome;
use Kongono\CacheRuntime\Exception\InvalidCacheRuntimeException;

/** Carries one validated lookup outcome and fresh detached evidence only. */
#[Api(ApiStatus::Experimental)]
final readonly class CacheLookupResult
{
    public function __construct(
        private CacheLookupOutcome $outcome,
        private CacheKeyIdentity $keyIdentity,
        private ?CacheEntryEvidence $entryEvidence = null,
    ) {
        if ((CacheLookupOutcome::FreshHit === $outcome) !== ($entryEvidence instanceof CacheEntryEvidence)) {
            throw InvalidCacheRuntimeException::invalidOperation('result_relation', 'lookup');
        }
    }

    public function outcome(): CacheLookupOutcome
    {
        return $this->outcome;
    }

    public function keyIdentity(): CacheKeyIdentity
    {
        return $this->keyIdentity;
    }

    public function entryEvidence(): ?CacheEntryEvidence
    {
        return $this->entryEvidence;
    }
}
