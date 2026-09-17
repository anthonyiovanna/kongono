<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterMemory\Configuration;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheAdapterContract\Adapter\CacheAdapterLimits;
use Kongono\CacheAdapterMemory\Internal\MemoryAdapterGuard;

/** Carries the finite process-local capacity profile. */
#[Api(ApiStatus::Experimental)]
final readonly class MemoryAdapterCapacity
{
    public function __construct(
        private int $maximumEntries,
        private int $maximumAggregateValueBytes,
        private int $maximumValueBytes,
    ) {
        MemoryAdapterGuard::capacity($maximumEntries, $maximumAggregateValueBytes, $maximumValueBytes);
    }

    public function maximumEntries(): int
    {
        return $this->maximumEntries;
    }

    public function maximumAggregateValueBytes(): int
    {
        return $this->maximumAggregateValueBytes;
    }

    public function maximumValueBytes(): int
    {
        return $this->maximumValueBytes;
    }

    public function limits(): CacheAdapterLimits
    {
        return new CacheAdapterLimits($this->maximumEntries, $this->maximumAggregateValueBytes, $this->maximumValueBytes);
    }
}
