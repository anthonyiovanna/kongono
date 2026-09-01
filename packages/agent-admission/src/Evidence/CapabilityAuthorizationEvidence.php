<?php

declare(strict_types=1);

namespace Kongono\AgentAdmission\Evidence;

use Kongono\AgentPrincipalContract\Capability\CapabilityIdentity;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Authorization\AuthorizationEvaluation;

/** Binds one requested capability to an application-supplied policy result. */
#[Api(ApiStatus::Experimental)]
final readonly class CapabilityAuthorizationEvidence
{
    public function __construct(
        private CapabilityIdentity $capabilityIdentity,
        private AuthorizationEvaluation $evaluation,
    ) {}

    public function capabilityIdentity(): CapabilityIdentity
    {
        return $this->capabilityIdentity;
    }

    public function evaluation(): AuthorizationEvaluation
    {
        return $this->evaluation;
    }
}
