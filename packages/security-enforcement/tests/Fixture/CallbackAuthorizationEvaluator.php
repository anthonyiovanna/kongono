<?php

declare(strict_types=1);

namespace Kongono\SecurityEnforcement\Tests\Fixture;

use Closure;
use Kongono\Authorization\AuthorizationEvaluator;
use Kongono\Authorization\AuthorizationPolicyReference;
use Kongono\Authorization\AuthorizationRequest;
use Kongono\IdentityAccess\AuthorizationDecision;

final readonly class CallbackAuthorizationEvaluator implements AuthorizationEvaluator
{
    /** @param Closure(AuthorizationRequest): AuthorizationDecision $callback */
    public function __construct(
        private AuthorizationPolicyReference $policy,
        private Closure $callback,
    ) {}

    public function policy(): AuthorizationPolicyReference
    {
        return $this->policy;
    }

    public function evaluate(AuthorizationRequest $request): AuthorizationDecision
    {
        return ($this->callback)($request);
    }
}
