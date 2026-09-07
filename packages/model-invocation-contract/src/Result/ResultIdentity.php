<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Result;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\Internal\ContractGuard;

/** Identifies normalized result evidence without proving provider delivery. */
#[Api(ApiStatus::Experimental)]
final readonly class ResultIdentity
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = ContractGuard::identity($value, 'result_identity_grammar');
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
