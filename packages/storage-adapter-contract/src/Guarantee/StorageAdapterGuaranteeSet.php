<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterContract\Guarantee;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageAdapterContract\Internal\AdapterContractGuard;

/** Holds a detached unique guarantee list in declaration order. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageAdapterGuaranteeSet
{
    /** @var list<StorageAdapterGuarantee> */
    private array $guarantees;

    /** @param array<array-key, mixed> $guarantees */
    public function __construct(array $guarantees)
    {
        $this->guarantees = AdapterContractGuard::guarantees($guarantees);
    }

    /** @return list<StorageAdapterGuarantee> */
    public function guarantees(): array
    {
        return [...$this->guarantees];
    }

    public function contains(StorageAdapterGuarantee $guarantee): bool
    {
        return in_array($guarantee, $this->guarantees, true);
    }
}
