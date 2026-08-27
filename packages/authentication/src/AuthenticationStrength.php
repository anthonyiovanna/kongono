<?php

declare(strict_types=1);

namespace Kongono\Authentication;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Authentication\Exception\InvalidAuthenticationValueException;

/** Carries one provider-neutral ordinal authentication-strength level. */
#[Api(ApiStatus::Experimental)]
final readonly class AuthenticationStrength
{
    public function __construct(private int $level)
    {
        if ($level < 1 || $level > 255) {
            throw InvalidAuthenticationValueException::strength();
        }
    }

    public function level(): int
    {
        return $this->level;
    }

    public function satisfies(self $required): bool
    {
        return $this->level >= $required->level;
    }
}
