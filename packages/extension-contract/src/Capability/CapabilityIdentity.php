<?php

declare(strict_types=1);

namespace Kongono\ExtensionContract\Capability;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionContract\Internal\ContractGuard;

/** Holds one exact versioned capability name without granting that capability. */
#[Api(ApiStatus::Experimental)]
final readonly class CapabilityIdentity
{
    private string $value;
    private int $version;

    public function __construct(string $value, int $version)
    {
        $this->value = ContractGuard::capabilityIdentity($value);
        $this->version = ContractGuard::capabilityVersion($version);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function version(): int
    {
        return $this->version;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value && $this->version === $other->version;
    }
}
