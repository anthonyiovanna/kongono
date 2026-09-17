<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterContract\Result;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheAdapterContract\Internal\AdapterContractGuard;
use Kongono\CacheContract\Entry\CacheEntryEvidence;
use Kongono\CacheContract\Identity\CacheKeyIdentity;
use Kongono\CacheContract\Outcome\CacheMutationOutcome;

/** Carries one closed mutation outcome and resulting evidence only when stored. */
#[Api(ApiStatus::Experimental)]
final readonly class CacheAdapterMutationResult
{
    public function __construct(
        private CacheMutationOutcome $outcome,
        private CacheKeyIdentity $keyIdentity,
        private ?CacheEntryEvidence $entryEvidence = null,
    ) {
        AdapterContractGuard::mutationResult($outcome, $keyIdentity, $entryEvidence);
    }

    public static function stored(CacheKeyIdentity $keyIdentity, CacheEntryEvidence $entryEvidence): self
    {
        return new self(CacheMutationOutcome::Stored, $keyIdentity, $entryEvidence);
    }

    public static function withoutEvidence(CacheMutationOutcome $outcome, CacheKeyIdentity $keyIdentity): self
    {
        return new self($outcome, $keyIdentity);
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
