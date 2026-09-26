<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterContract\Guarantee;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageAdapterContract\Internal\AdapterContractGuard;

/** Holds a detached unique limitation list in declaration order. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageAdapterLimitationSet
{
    /** @var list<StorageAdapterLimitation> */
    private array $limitations;

    /** @param array<array-key, mixed> $limitations */
    public function __construct(array $limitations)
    {
        $this->limitations = AdapterContractGuard::limitations($limitations);
    }

    /** @return list<StorageAdapterLimitation> */
    public function limitations(): array
    {
        return [...$this->limitations];
    }

    public function contains(StorageAdapterLimitation $limitation): bool
    {
        return in_array($limitation, $this->limitations, true);
    }
}
