<?php

declare(strict_types=1);

namespace Kongono\Authentication;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Authentication\Exception\InvalidAuthenticationValueException;

/** Carries one explicit non-negative Unix second without reading a clock. */
#[Api(ApiStatus::Experimental)]
final readonly class AuthenticationInstant
{
    public function __construct(private int $seconds)
    {
        if ($seconds < 0) {
            throw InvalidAuthenticationValueException::freshness();
        }
    }

    public function seconds(): int
    {
        return $this->seconds;
    }

    public function isAfter(self $other): bool
    {
        return $this->seconds > $other->seconds;
    }

    public function elapsedSince(self $earlier): int
    {
        if ($this->seconds < $earlier->seconds) {
            throw InvalidAuthenticationValueException::freshness();
        }

        return $this->seconds - $earlier->seconds;
    }
}
