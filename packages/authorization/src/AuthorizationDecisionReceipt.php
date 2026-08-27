<?php

declare(strict_types=1);

namespace Kongono\Authorization;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\IdentityAccess\Action;
use Kongono\IdentityAccess\AuthorizationDecision;
use Kongono\IdentityAccess\ResourceType;
use Kongono\IdentityAccess\SecurityContextState;

/** Records bounded non-secret facts about one authorization evaluation result. */
#[Api(ApiStatus::Experimental)]
final readonly class AuthorizationDecisionReceipt
{
    private function __construct(
        private AuthorizationDecision $decision,
        private AuthorizationReceiptReason $reason,
        private AuthorizationPolicyReference $policy,
        private Action $action,
        private ResourceType $resourceType,
        private SecurityContextState $securityContextState,
        private AuthorizationEnvironmentProfile $environmentProfile,
    ) {}

    public static function evaluated(
        AuthorizationRequest $request,
        AuthorizationDecision $decision,
    ): self {
        return self::fromRequest($request, $decision, AuthorizationReceiptReason::Evaluated);
    }

    public static function policyMismatch(AuthorizationRequest $request): self
    {
        return self::fromRequest(
            $request,
            AuthorizationDecision::Indeterminate,
            AuthorizationReceiptReason::PolicyMismatch,
        );
    }

    public static function unsupportedPolicy(AuthorizationRequest $request): self
    {
        return self::fromRequest(
            $request,
            AuthorizationDecision::Indeterminate,
            AuthorizationReceiptReason::UnsupportedPolicy,
        );
    }

    public function decision(): AuthorizationDecision
    {
        return $this->decision;
    }

    public function reason(): AuthorizationReceiptReason
    {
        return $this->reason;
    }

    public function policy(): AuthorizationPolicyReference
    {
        return $this->policy;
    }

    public function action(): Action
    {
        return $this->action;
    }

    public function resourceType(): ResourceType
    {
        return $this->resourceType;
    }

    public function securityContextState(): SecurityContextState
    {
        return $this->securityContextState;
    }

    public function environmentProfile(): AuthorizationEnvironmentProfile
    {
        return $this->environmentProfile;
    }

    private static function fromRequest(
        AuthorizationRequest $request,
        AuthorizationDecision $decision,
        AuthorizationReceiptReason $reason,
    ): self {
        return new self(
            $decision,
            $reason,
            $request->expectedPolicy(),
            $request->action(),
            $request->resource()->type(),
            $request->securityContext()->state(),
            $request->environment()->profile(),
        );
    }
}
