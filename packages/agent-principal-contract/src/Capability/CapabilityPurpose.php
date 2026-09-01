<?php

declare(strict_types=1);

namespace Kongono\AgentPrincipalContract\Capability;

use Kongono\AgentPrincipalContract\Internal\ContractGuard;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Holds bounded inert capability-purpose text that is never a grant. */
#[Api(ApiStatus::Experimental)]
final readonly class CapabilityPurpose
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = ContractGuard::purpose($value, 'capability_purpose_grammar');
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
