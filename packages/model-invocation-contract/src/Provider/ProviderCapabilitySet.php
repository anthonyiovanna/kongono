<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Provider;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\Exception\InvalidModelInvocationContractException;

/** Stores the canonical capabilities declared by one inert profile. */
#[Api(ApiStatus::Experimental)]
final readonly class ProviderCapabilitySet
{
    /** @var list<ProviderCapability> */
    private array $capabilities;

    /** @param list<ProviderCapability> $capabilities */
    public function __construct(array $capabilities)
    {
        self::validate($capabilities);
        $this->capabilities = [...$capabilities];
    }

    /** @param array<array-key, mixed> $capabilities */
    private static function validate(array $capabilities): void
    {
        if (!array_is_list($capabilities)) {
            throw InvalidModelInvocationContractException::invalidProfile('capabilities_not_list', count($capabilities));
        }
        if ([] === $capabilities || count($capabilities) > 6) {
            throw InvalidModelInvocationContractException::invalidProfile('capability_count', count($capabilities));
        }

        $order = array_flip(array_map(static fn(ProviderCapability $capability): string => $capability->value, ProviderCapability::cases()));
        $previous = -1;
        $seen = [];
        foreach ($capabilities as $position => $capability) {
            if (!$capability instanceof ProviderCapability) {
                throw InvalidModelInvocationContractException::invalidProfile('invalid_capability_entry', count($capabilities), $position);
            }
            if (isset($seen[$capability->value])) {
                throw InvalidModelInvocationContractException::invalidProfile('duplicate_capability', count($capabilities), $position);
            }
            $current = $order[$capability->value];
            if ($current <= $previous) {
                throw InvalidModelInvocationContractException::invalidProfile('non_canonical_capability_order', count($capabilities), $position);
            }
            $seen[$capability->value] = true;
            $previous = $current;
        }

    }

    /** @return list<ProviderCapability> */
    public function capabilities(): array
    {
        return [...$this->capabilities];
    }

    public function supports(ProviderCapability $capability): bool
    {
        return in_array($capability, $this->capabilities, true);
    }

    public function equals(self $other): bool
    {
        return $this->capabilities === $other->capabilities;
    }
}
