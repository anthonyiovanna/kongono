<?php

declare(strict_types=1);

namespace Kongono\CacheContract\Freshness;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheContract\Internal\ContractGuard;

/** Carries one finite positive cache lifetime in microseconds. */
#[Api(ApiStatus::Experimental)]
final readonly class CacheLifetime
{
    private int $microseconds;

    public function __construct(int $microseconds)
    {
        $this->microseconds = ContractGuard::cacheLifetime($microseconds);
    }

    public function microseconds(): int
    {
        return $this->microseconds;
    }

    public function equals(self $other): bool
    {
        return $this->microseconds === $other->microseconds;
    }
}
