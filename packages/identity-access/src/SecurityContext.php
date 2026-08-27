<?php

declare(strict_types=1);

namespace Kongono\IdentityAccess;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Carries one explicit immutable anonymous or authenticated operation context. */
#[Api(ApiStatus::Experimental)]
final readonly class SecurityContext
{
    private function __construct(
        private SecurityContextState $state,
        private ?PrincipalReference $principal,
    ) {}

    public static function anonymous(): self
    {
        return new self(SecurityContextState::Anonymous, null);
    }

    public static function authenticated(PrincipalReference $principal): self
    {
        return new self(SecurityContextState::Authenticated, $principal);
    }

    public function state(): SecurityContextState
    {
        return $this->state;
    }

    public function principal(): ?PrincipalReference
    {
        return $this->principal;
    }
}
