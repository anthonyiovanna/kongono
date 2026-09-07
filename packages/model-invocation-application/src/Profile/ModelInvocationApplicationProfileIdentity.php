<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationApplication\Profile;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationApplication\Exception\InvalidModelInvocationApplicationProfileException;

/** Identifies one exact application profile without selecting an adapter. */
#[Api(ApiStatus::Experimental)]
final readonly class ModelInvocationApplicationProfileIdentity
{
    public function __construct(private string $value)
    {
        if (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $value)) {
            throw InvalidModelInvocationApplicationProfileException::invalid('profile_identity_grammar');
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
