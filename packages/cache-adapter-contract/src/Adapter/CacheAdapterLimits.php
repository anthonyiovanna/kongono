<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterContract\Adapter;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheAdapterContract\Internal\AdapterContractGuard;

/** Carries finite adapter capacity declarations without heap-accounting claims. */
#[Api(ApiStatus::Experimental)]
final readonly class CacheAdapterLimits
{
    public function __construct(
        private int $maximumEntries,
        private int $maximumAggregateValueBytes,
        private int $maximumValueBytes,
    ) {
        AdapterContractGuard::limits($maximumEntries, $maximumAggregateValueBytes, $maximumValueBytes);
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

    public function equals(self $other): bool
    {
        return $this->maximumEntries === $other->maximumEntries
            && $this->maximumAggregateValueBytes === $other->maximumAggregateValueBytes
            && $this->maximumValueBytes === $other->maximumValueBytes;
    }
}
