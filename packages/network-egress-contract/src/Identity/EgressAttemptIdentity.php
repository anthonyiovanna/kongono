<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressContract\Identity;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressContract\Internal\ContractGuard;

/** Identifies one transport-attempt claim without authorizing a retry. */
#[Api(ApiStatus::Experimental)]
final readonly class EgressAttemptIdentity
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = ContractGuard::identity($value, 'attempt_identity_grammar');
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
