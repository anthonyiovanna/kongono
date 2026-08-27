<?php

declare(strict_types=1);

namespace Kongono\Authentication;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Authentication\Exception\InvalidAuthenticationValueException;

/** Defines explicit minimum strength and maximum authentication age. */
#[Api(ApiStatus::Experimental)]
final readonly class AuthenticationRequirement
{
    public function __construct(
        private AuthenticationStrength $minimumStrength,
        private int $maximumAgeSeconds,
    ) {
        if ($maximumAgeSeconds < 0 || $maximumAgeSeconds > 31_536_000) {
            throw InvalidAuthenticationValueException::freshness();
        }
    }

    public function minimumStrength(): AuthenticationStrength
    {
        return $this->minimumStrength;
    }

    public function maximumAgeSeconds(): int
    {
        return $this->maximumAgeSeconds;
    }
}
