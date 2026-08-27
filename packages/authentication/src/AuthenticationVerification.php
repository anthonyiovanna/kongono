<?php

declare(strict_types=1);

namespace Kongono\Authentication;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\IdentityAccess\AuthenticationOutcome;

/** Pairs one neutral authentication outcome with facts only on success. */
#[Api(ApiStatus::Experimental)]
final readonly class AuthenticationVerification
{
    private function __construct(
        private AuthenticationOutcome $outcome,
        private ?AuthenticationFacts $facts,
    ) {}

    public static function authenticated(AuthenticationFacts $facts): self
    {
        return new self(AuthenticationOutcome::authenticated($facts->principal()), $facts);
    }

    public static function absent(): self
    {
        return new self(AuthenticationOutcome::absent(), null);
    }

    public static function invalid(): self
    {
        return new self(AuthenticationOutcome::invalid(), null);
    }

    public static function expired(): self
    {
        return new self(AuthenticationOutcome::expired(), null);
    }

    public static function replayed(): self
    {
        return new self(AuthenticationOutcome::replayed(), null);
    }

    public static function unsupported(): self
    {
        return new self(AuthenticationOutcome::unsupported(), null);
    }

    public static function ambiguous(): self
    {
        return new self(AuthenticationOutcome::ambiguous(), null);
    }

    public static function unavailable(): self
    {
        return new self(AuthenticationOutcome::unavailable(), null);
    }

    public function outcome(): AuthenticationOutcome
    {
        return $this->outcome;
    }

    public function facts(): ?AuthenticationFacts
    {
        return $this->facts;
    }
}
