<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Invocation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\Internal\ContractGuard;

/** Identifies one invocation claim without proving execution. */
#[Api(ApiStatus::Experimental)]
final readonly class InvocationIdentity
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = ContractGuard::identity($value, 'invocation_identity_grammar');
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
