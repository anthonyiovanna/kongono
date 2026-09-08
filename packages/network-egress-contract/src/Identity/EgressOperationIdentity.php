<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressContract\Identity;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressContract\Internal\ContractGuard;

/** Identifies one caller-declared egress operation without granting authority. */
#[Api(ApiStatus::Experimental)]
final readonly class EgressOperationIdentity
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = ContractGuard::identity($value, 'operation_identity_grammar');
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
