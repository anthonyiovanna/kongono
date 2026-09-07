<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Profile;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\Internal\ContractGuard;

/** Identifies one application-owned inert model profile reference. */
#[Api(ApiStatus::Experimental)]
final readonly class ProfileIdentity
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = ContractGuard::identity($value, 'profile_identity_grammar');
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
