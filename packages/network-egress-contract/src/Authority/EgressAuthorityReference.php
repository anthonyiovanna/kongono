<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressContract\Authority;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressContract\Exception\InvalidNetworkEgressContractException;
use Kongono\NetworkEgressContract\Internal\ContractGuard;

/** References application-owned authority evidence without carrying a decision. */
#[Api(ApiStatus::Experimental)]
final readonly class EgressAuthorityReference
{
    private string $identity;

    public function __construct(
        string $identity,
        private int $revision,
    ) {
        $this->identity = ContractGuard::identity($identity, 'authority_identity_grammar');
        if ($revision < 1) {
            throw InvalidNetworkEgressContractException::invalidReference('authority_revision_range');
        }
    }

    public function identity(): string
    {
        return $this->identity;
    }

    public function revision(): int
    {
        return $this->revision;
    }

    public function equals(self $other): bool
    {
        return $this->identity === $other->identity && $this->revision === $other->revision;
    }
}
