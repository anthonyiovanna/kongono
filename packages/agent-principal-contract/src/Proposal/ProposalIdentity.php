<?php

declare(strict_types=1);

namespace Kongono\AgentPrincipalContract\Proposal;

use Kongono\AgentPrincipalContract\Internal\ContractGuard;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Holds one bounded proposal identity without admitting the proposal. */
#[Api(ApiStatus::Experimental)]
final readonly class ProposalIdentity
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = ContractGuard::identity($value, 'proposal_identity_grammar');
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
