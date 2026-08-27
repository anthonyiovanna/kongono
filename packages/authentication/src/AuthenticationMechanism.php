<?php

declare(strict_types=1);

namespace Kongono\Authentication;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Authentication\Exception\InvalidAuthenticationValueException;

/** Identifies one exact application-selected authentication mechanism. */
#[Api(ApiStatus::Experimental)]
final readonly class AuthenticationMechanism
{
    public function __construct(private string $value)
    {
        if (1 !== preg_match('/^[a-z][a-z0-9]*(?:[._:-][a-z0-9]+)*$/D', $value) || strlen($value) > 64) {
            throw InvalidAuthenticationValueException::validation();
        }
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
