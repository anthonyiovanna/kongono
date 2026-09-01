<?php

declare(strict_types=1);

namespace Kongono\AgentAdmission\Identity;

use Kongono\AgentAdmission\Exception\InvalidAgentAdmissionException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Holds the exact lowercase SHA-256 identity of one authority plan. */
#[Api(ApiStatus::Experimental)]
final readonly class AgentAdmissionIdentity
{
    public function __construct(private string $value)
    {
        if (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $value)) {
            throw InvalidAgentAdmissionException::invalid('admission_identity_grammar');
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return hash_equals($this->value, $other->value);
    }
}
