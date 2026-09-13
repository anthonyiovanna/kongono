<?php

declare(strict_types=1);

namespace Kongono\ViewApplication\Profile;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewApplication\Exception\InvalidViewApplicationException;

/** Identifies one exact accepted source and compiled artifact evidence binding. */
#[Api(ApiStatus::Experimental)]
final readonly class ViewApplicationProfileIdentity
{
    public function __construct(private string $value)
    {
        if (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $value)) {
            throw InvalidViewApplicationException::invalid('identity_grammar');
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
