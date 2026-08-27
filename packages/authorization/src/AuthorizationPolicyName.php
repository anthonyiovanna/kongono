<?php

declare(strict_types=1);

namespace Kongono\Authorization;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Authorization\Exception\InvalidAuthorizationValueException;

/** Identifies one application-owned authorization policy by an exact canonical name. */
#[Api(ApiStatus::Experimental)]
final readonly class AuthorizationPolicyName
{
    public function __construct(private string $value)
    {
        if (
            '' === $value
            || strlen($value) > 64
            || 1 !== preg_match('/^[a-z][a-z0-9]*(?:[._:-][a-z0-9]+)*$/D', $value)
        ) {
            throw InvalidAuthorizationValueException::validation();
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
