<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressContract\Capability;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressContract\Exception\InvalidNetworkEgressContractException;

/** Stores a canonical declared capability set without discovering a transport. */
#[Api(ApiStatus::Experimental)]
final readonly class TransportCapabilitySet
{
    /** @var list<TransportCapability> */
    private array $capabilities;

    /** @param list<TransportCapability> $capabilities */
    public function __construct(array $capabilities)
    {
        self::validate($capabilities);
        $this->capabilities = [...$capabilities];
    }

    /** @return list<TransportCapability> */
    public function all(): array
    {
        return [...$this->capabilities];
    }

    public function contains(TransportCapability $capability): bool
    {
        return in_array($capability, $this->capabilities, true);
    }

    public function count(): int
    {
        return count($this->capabilities);
    }

    /** @param array<array-key, mixed> $capabilities */
    private static function validate(array $capabilities): void
    {
        if (!array_is_list($capabilities)) {
            throw InvalidNetworkEgressContractException::invalidCapabilityEvidence('capabilities_not_list', count($capabilities));
        }
        if ([] === $capabilities) {
            throw InvalidNetworkEgressContractException::invalidCapabilityEvidence('capability_count', 0);
        }
        if (count($capabilities) > 11) {
            throw InvalidNetworkEgressContractException::contractLimitExceeded('capability_count', 11, count($capabilities));
        }

        $order = array_flip(array_map(static fn(TransportCapability $capability): string => $capability->value, TransportCapability::cases()));
        $previous = -1;
        $seen = [];
        foreach ($capabilities as $position => $capability) {
            if (!$capability instanceof TransportCapability) {
                throw InvalidNetworkEgressContractException::invalidCapabilityEvidence('invalid_capability_entry', count($capabilities), $position);
            }
            if (isset($seen[$capability->value])) {
                throw InvalidNetworkEgressContractException::invalidCapabilityEvidence('duplicate_capability', count($capabilities), $position);
            }
            $current = $order[$capability->value];
            if ($current <= $previous) {
                throw InvalidNetworkEgressContractException::invalidCapabilityEvidence('non_canonical_capability_order', count($capabilities), $position);
            }
            $seen[$capability->value] = true;
            $previous = $current;
        }
    }
}
