<?php

declare(strict_types=1);

namespace Kongono\Authorization;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\IdentityAccess\Action;
use Kongono\IdentityAccess\ResourceReference;
use Kongono\IdentityAccess\SecurityContext;

/** Carries every explicit immutable input for one authorization evaluation. */
#[Api(ApiStatus::Experimental)]
final readonly class AuthorizationRequest
{
    public function __construct(
        private SecurityContext $securityContext,
        private Action $action,
        private ResourceReference $resource,
        private AuthorizationPolicyReference $expectedPolicy,
        private AuthorizationPolicyEvidence $policyEvidence,
        private AuthorizationEnvironmentEvidence $environment,
    ) {}

    public function securityContext(): SecurityContext
    {
        return $this->securityContext;
    }

    public function action(): Action
    {
        return $this->action;
    }

    public function resource(): ResourceReference
    {
        return $this->resource;
    }

    public function expectedPolicy(): AuthorizationPolicyReference
    {
        return $this->expectedPolicy;
    }

    public function policyEvidence(): AuthorizationPolicyEvidence
    {
        return $this->policyEvidence;
    }

    public function environment(): AuthorizationEnvironmentEvidence
    {
        return $this->environment;
    }
}
