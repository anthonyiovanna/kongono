<?php

declare(strict_types=1);

namespace Kongono\CacheContract\Freshness;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheContract\Internal\ContractGuard;
use Kongono\CacheContract\Time\CacheInstant;

/** Relates explicit storage and expiry instants without ambient time. */
#[Api(ApiStatus::Experimental)]
final readonly class CacheFreshnessWindow
{
    public function __construct(
        private CacheInstant $storedAt,
        private CacheInstant $expiresAt,
    ) {
        ContractGuard::freshnessWindow($storedAt, $expiresAt);
    }

    public function storedAt(): CacheInstant
    {
        return $this->storedAt;
    }

    public function expiresAt(): CacheInstant
    {
        return $this->expiresAt;
    }

    public function lifetimeMicroseconds(): int
    {
        return $this->expiresAt->microseconds() - $this->storedAt->microseconds();
    }

    public function freshnessAt(CacheInstant $now): CacheFreshness
    {
        if ($now->compareTo($this->storedAt) < 0) {
            throw ContractGuard::nowBeforeStoredAt();
        }

        return $now->compareTo($this->expiresAt) < 0 ? CacheFreshness::Fresh : CacheFreshness::Expired;
    }

    public function equals(self $other): bool
    {
        return $this->storedAt->equals($other->storedAt) && $this->expiresAt->equals($other->expiresAt);
    }
}
