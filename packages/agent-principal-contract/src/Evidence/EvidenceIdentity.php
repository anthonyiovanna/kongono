<?php

declare(strict_types=1);

namespace Kongono\AgentPrincipalContract\Evidence;

use Kongono\AgentPrincipalContract\Internal\ContractGuard;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Holds one bounded evidence identity without loading evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class EvidenceIdentity
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = ContractGuard::identity($value, 'evidence_identity_grammar');
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
