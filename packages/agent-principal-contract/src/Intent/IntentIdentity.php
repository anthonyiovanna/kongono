<?php

declare(strict_types=1);

namespace Kongono\AgentPrincipalContract\Intent;

use Kongono\AgentPrincipalContract\Internal\ContractGuard;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Holds one bounded intent identity without interpreting intent. */
#[Api(ApiStatus::Experimental)]
final readonly class IntentIdentity
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = ContractGuard::identity($value, 'intent_identity_grammar');
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
