<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressApplication\Profile;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressApplication\Exception\InvalidNetworkEgressApplicationProfileException;

/** Identifies one exact policy-bound application profile as inert evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class NetworkEgressApplicationProfileIdentity
{
    public function __construct(private string $value)
    {
        if (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $value)) {
            throw InvalidNetworkEgressApplicationProfileException::invalid('profile_identity_grammar');
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
