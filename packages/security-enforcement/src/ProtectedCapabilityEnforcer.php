<?php

declare(strict_types=1);

namespace Kongono\SecurityEnforcement;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Authorization\AuthorizationEngine;
use Kongono\Authorization\AuthorizationRequest;
use Kongono\IdentityAccess\AuthorizationDecision;
use Kongono\IdentityAccess\SecurityContext;
use Kongono\SecurityEnforcement\Exception\ProtectedCapabilityEnforcementException;

/** Evaluates exact authorization inputs immediately around one protected call. */
#[Api(ApiStatus::Experimental)]
final class ProtectedCapabilityEnforcer
{
    /**
     * @template TResult
     * @param ProtectedCapability<TResult> $capability
     * @return TResult
     */
    public function enforce(
        SecurityContext $securityContext,
        AuthorizationEngine $authorization,
        AuthorizationRequest $request,
        ProtectedCapability $capability,
    ): mixed {
        if ($request->securityContext() !== $securityContext) {
            throw ProtectedCapabilityEnforcementException::contextMismatch();
        }
        if (!$request->action()->equals($capability->action())) {
            throw ProtectedCapabilityEnforcementException::actionMismatch();
        }
        if (!$request->resource()->equals($capability->resource())) {
            throw ProtectedCapabilityEnforcementException::resourceMismatch();
        }

        $evaluation = $authorization->authorize($request);
        $decision = $evaluation->decision();
        if (AuthorizationDecision::Permit !== $decision) {
            throw ProtectedCapabilityEnforcementException::decisionRejected();
        }

        return $capability->execute();
    }
}
