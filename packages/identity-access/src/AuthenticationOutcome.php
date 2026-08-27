<?php

declare(strict_types=1);

namespace Kongono\IdentityAccess;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Carries one presentation-neutral authentication outcome and optional principal. */
#[Api(ApiStatus::Experimental)]
final readonly class AuthenticationOutcome
{
    private function __construct(
        private AuthenticationOutcomeKind $kind,
        private ?PrincipalReference $principal,
    ) {}

    public static function authenticated(PrincipalReference $principal): self
    {
        return new self(AuthenticationOutcomeKind::Authenticated, $principal);
    }

    public static function absent(): self
    {
        return new self(AuthenticationOutcomeKind::Absent, null);
    }

    public static function invalid(): self
    {
        return new self(AuthenticationOutcomeKind::Invalid, null);
    }

    public static function expired(): self
    {
        return new self(AuthenticationOutcomeKind::Expired, null);
    }

    public static function replayed(): self
    {
        return new self(AuthenticationOutcomeKind::Replayed, null);
    }

    public static function unsupported(): self
    {
        return new self(AuthenticationOutcomeKind::Unsupported, null);
    }

    public static function ambiguous(): self
    {
        return new self(AuthenticationOutcomeKind::Ambiguous, null);
    }

    public static function unavailable(): self
    {
        return new self(AuthenticationOutcomeKind::Unavailable, null);
    }

    public function kind(): AuthenticationOutcomeKind
    {
        return $this->kind;
    }

    public function principal(): ?PrincipalReference
    {
        return $this->principal;
    }
}
