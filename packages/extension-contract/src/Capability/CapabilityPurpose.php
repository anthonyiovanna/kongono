<?php

declare(strict_types=1);

namespace Kongono\ExtensionContract\Capability;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionContract\Internal\ContractGuard;

/** Holds one bounded purpose label for an inert capability request. */
#[Api(ApiStatus::Experimental)]
final readonly class CapabilityPurpose
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = ContractGuard::capabilityPurpose($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
