<?php

declare(strict_types=1);

namespace Kongono\Authorization;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Authorization\Exception\InvalidAuthorizationValueException;

/** Carries one exact opaque policy version token without ordering semantics. */
#[Api(ApiStatus::Experimental)]
final readonly class AuthorizationPolicyVersion
{
    public function __construct(private string $value)
    {
        if (
            '' === $value
            || strlen($value) > 128
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
