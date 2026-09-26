<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Capability;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageContract\Exception\InvalidStorageContractException;

/** Holds detached capability-support evidence in declaration order. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageCapabilitySet
{
    /** @var list<StorageCapability> */
    private array $capabilities;

    /** @param array<array-key, mixed> $capabilities */
    public function __construct(array $capabilities)
    {
        if (!array_is_list($capabilities)) {
            throw InvalidStorageContractException::invalidCapability('capabilities_not_list');
        }
        $present = [];
        foreach ($capabilities as $capability) {
            if (!$capability instanceof StorageCapability) {
                throw InvalidStorageContractException::invalidCapability('invalid_capability_entry');
            }
            $present[$capability->value] = true;
        }
        $ordered = [];
        foreach (StorageCapability::cases() as $capability) {
            if (isset($present[$capability->value])) {
                $ordered[] = $capability;
            }
        }
        $this->capabilities = $ordered;
    }

    public function contains(StorageCapability $capability): bool
    {
        return in_array($capability, $this->capabilities, true);
    }

    /** @return list<StorageCapability> */
    public function capabilities(): array
    {
        return [...$this->capabilities];
    }

    public function equals(self $other): bool
    {
        return $this->capabilities === $other->capabilities;
    }
}
