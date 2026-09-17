<?php

declare(strict_types=1);

namespace Kongono\CacheRuntime\Result;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheContract\Entry\CacheEntryEvidence;
use Kongono\CacheContract\Identity\CacheKeyIdentity;
use Kongono\CacheContract\Outcome\CacheMutationOutcome;
use Kongono\CacheRuntime\Exception\InvalidCacheRuntimeException;

/** Carries one validated mutation outcome and detached stored evidence only. */
#[Api(ApiStatus::Experimental)]
final readonly class CacheMutationResult
{
    public function __construct(
        private CacheMutationOutcome $outcome,
        private CacheKeyIdentity $keyIdentity,
        private ?CacheEntryEvidence $entryEvidence = null,
    ) {
        if ((CacheMutationOutcome::Stored === $outcome) !== ($entryEvidence instanceof CacheEntryEvidence)) {
            throw InvalidCacheRuntimeException::invalidOperation('result_relation');
        }
    }

    public function outcome(): CacheMutationOutcome
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
