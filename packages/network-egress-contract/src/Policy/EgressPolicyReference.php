<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressContract\Policy;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressContract\Internal\ContractGuard;

/** References one exact application-owned policy without loading or evaluating it. */
#[Api(ApiStatus::Experimental)]
final readonly class EgressPolicyReference
{
    private string $identity;
    private string $sha256;

    public function __construct(
        string $identity,
        string $sha256,
    ) {
        $this->identity = ContractGuard::identity($identity, 'policy_identity_grammar');
        $this->sha256 = ContractGuard::sha256($sha256);
    }

    public function identity(): string
    {
        return $this->identity;
    }

    public function sha256(): string
    {
        return $this->sha256;
    }

    public function equals(self $other): bool
    {
        return $this->identity === $other->identity && $this->sha256 === $other->sha256;
    }
}
