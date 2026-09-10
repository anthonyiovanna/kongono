<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceApplication\Profile;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceApplication\Exception\InvalidExternalServiceApplicationProfileException;

/** Identifies one exact binding-and-policy application profile as inert evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class ExternalServiceApplicationProfileIdentity
{
    public function __construct(private string $value)
    {
        if (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $value)) {
            throw InvalidExternalServiceApplicationProfileException::invalid('profile_identity_grammar');
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
