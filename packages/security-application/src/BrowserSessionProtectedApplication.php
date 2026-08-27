<?php

declare(strict_types=1);

namespace Kongono\SecurityApplication;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Authentication\AuthenticationInstant;
use Kongono\Authorization\AuthorizationEngine;
use Kongono\Authorization\AuthorizationEnvironmentEvidence;
use Kongono\Authorization\AuthorizationRequest;
use Kongono\SecurityEnforcement\ProtectedCapability;
use Kongono\SecurityEnforcement\ProtectedCapabilityEnforcer;
use Kongono\SecurityHttp\BrowserSessionSecurityGateway;
use Kongono\SecurityPolicyBuild\Workflow\CurrentSecurityPolicy;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/** Owns one exact request-to-security-operation-to-enforcement lifecycle. */
#[Api(ApiStatus::Experimental)]
final readonly class BrowserSessionProtectedApplication
{
    public function __construct(
        private BrowserSessionSecurityGateway $gateway,
        private AuthorizationEngine $authorization,
        private ProtectedCapabilityEnforcer $enforcer,
        private CurrentSecurityPolicy $policy,
    ) {}

    /**
     * @template TResult
     * @param ProtectedCapability<TResult> $capability
     * @return TResult
     */
    public function execute(
        ServerRequestInterface $request,
        AuthenticationInstant $now,
        AuthorizationEnvironmentEvidence $environment,
        ProtectedCapability $capability,
    ): mixed {
        $operation = $this->gateway->begin($request, $now);
        $primaryFailure = null;
        try {
            $context = $operation->securityContext();
            $evidence = $this->policy->evidence();
            $authorizationRequest = new AuthorizationRequest(
                $context,
                $capability->action(),
                $capability->resource(),
                $evidence->reference(),
                $evidence,
                $environment,
            );

            return $this->enforcer->enforce($context, $this->authorization, $authorizationRequest, $capability);
        } catch (Throwable $failure) {
            $primaryFailure = $failure;
            throw $failure;
        } finally {
            $operation->close($primaryFailure);
        }
    }
}
