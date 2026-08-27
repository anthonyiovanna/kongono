<?php

declare(strict_types=1);

namespace KongonoSecurityMinimal;

use Kongono\Authorization\AuthorizationEvaluator;
use Kongono\Authorization\AuthorizationPolicyReference;
use Kongono\Authorization\AuthorizationRequest;
use Kongono\IdentityAccess\AuthorizationDecision;
use Kongono\IdentityAccess\SecurityContextState;

/** Implements only one exact public-example decision and denies every other request. */
final readonly class PublicExampleEvaluator implements AuthorizationEvaluator
{
    private function __construct(private AuthorizationPolicyReference $policy, private bool $permitExactPublicExample) {}

    public static function permitting(AuthorizationPolicyReference $policy): self
    {
        return new self($policy, true);
    }

    public static function denying(AuthorizationPolicyReference $policy): self
    {
        return new self($policy, false);
    }

    public function policy(): AuthorizationPolicyReference
    {
        return $this->policy;
    }

    public function evaluate(AuthorizationRequest $request): AuthorizationDecision
    {
        if (
            $this->permitExactPublicExample
            && SecurityContextState::Anonymous === $request->securityContext()->state()
            && 'public.example.read' === $request->action()->value()
            && 'public.example' === $request->resource()->type()->value()
            && 'fixed-public-result' === $request->resource()->id()->value()
            && 'public.example.v1' === $request->environment()->profile()->value()
        ) {
            return AuthorizationDecision::Permit;
        }

        return AuthorizationDecision::Deny;
    }
}
