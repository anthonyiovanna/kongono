<?php

declare(strict_types=1);

namespace Kongono\CacheContract\Time;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheContract\Internal\ContractGuard;

/** Carries caller-supplied Unix-epoch microseconds without reading a clock. */
#[Api(ApiStatus::Experimental)]
final readonly class CacheInstant
{
    private int $microseconds;

    public function __construct(int $microseconds)
    {
        $this->microseconds = ContractGuard::cacheInstant($microseconds);
    }

    public function microseconds(): int
    {
        return $this->microseconds;
    }

    public function compareTo(self $other): int
    {
        return $this->microseconds <=> $other->microseconds;
    }

    public function equals(self $other): bool
    {
        return $this->microseconds === $other->microseconds;
    }
}
