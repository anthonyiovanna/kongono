<?php

declare(strict_types=1);

namespace Kongono\AgentPrincipalContract\Intent;

use Kongono\AgentPrincipalContract\Internal\ContractGuard;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Holds bounded inert intent-purpose text that grants no authority. */
#[Api(ApiStatus::Experimental)]
final readonly class IntentPurpose
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = ContractGuard::purpose($value, 'intent_purpose_grammar');
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
