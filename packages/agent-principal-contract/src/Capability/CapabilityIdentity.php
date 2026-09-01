<?php

declare(strict_types=1);

namespace Kongono\AgentPrincipalContract\Capability;

use Kongono\AgentPrincipalContract\Internal\ContractGuard;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Holds one bounded capability identity without resolving a capability. */
#[Api(ApiStatus::Experimental)]
final readonly class CapabilityIdentity
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = ContractGuard::identity($value, 'capability_identity_grammar');
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
