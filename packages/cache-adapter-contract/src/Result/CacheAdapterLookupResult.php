<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterContract\Result;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheAdapterContract\Internal\AdapterContractGuard;
use Kongono\CacheContract\Entry\CacheEntryEvidence;
use Kongono\CacheContract\Identity\CacheKeyIdentity;

/** Carries one closed lookup state and complete evidence only when found. */
#[Api(ApiStatus::Experimental)]
final readonly class CacheAdapterLookupResult
{
    public function __construct(
        private CacheAdapterLookupStatus $status,
        private CacheKeyIdentity $keyIdentity,
        private ?CacheEntryEvidence $entryEvidence = null,
    ) {
        AdapterContractGuard::lookupResult($status, $keyIdentity, $entryEvidence);
    }

    public static function found(CacheKeyIdentity $keyIdentity, CacheEntryEvidence $entryEvidence): self
    {
        return new self(CacheAdapterLookupStatus::Found, $keyIdentity, $entryEvidence);
    }

    public static function miss(CacheKeyIdentity $keyIdentity): self
    {
        return new self(CacheAdapterLookupStatus::Miss, $keyIdentity);
    }

    public static function rejected(CacheKeyIdentity $keyIdentity): self
    {
        return new self(CacheAdapterLookupStatus::Rejected, $keyIdentity);
    }

    public static function unavailable(CacheKeyIdentity $keyIdentity): self
    {
        return new self(CacheAdapterLookupStatus::Unavailable, $keyIdentity);
    }

    public function status(): CacheAdapterLookupStatus
    {
        return $this->status;
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
