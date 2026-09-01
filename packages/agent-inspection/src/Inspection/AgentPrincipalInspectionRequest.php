<?php

declare(strict_types=1);

namespace Kongono\AgentInspection\Inspection;

use Kongono\AgentApplication\Generated\GeneratedAgentWiring;
use Kongono\AgentApplication\Profile\AgentApplicationProfile;
use Kongono\AgentRuntime\Runtime\AgentRuntimeResult;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Retains only the explicit detached profile, wiring, and optional completed result. */
#[Api(ApiStatus::Experimental)]
final readonly class AgentPrincipalInspectionRequest
{
    public function __construct(
        private AgentApplicationProfile $profile,
        private GeneratedAgentWiring $wiring,
        private ?AgentRuntimeResult $runtimeResult = null,
    ) {}

    public function profile(): AgentApplicationProfile
    {
        return $this->profile;
    }

    public function wiring(): GeneratedAgentWiring
    {
        return $this->wiring;
    }

    public function runtimeResult(): ?AgentRuntimeResult
    {
        return $this->runtimeResult;
    }
}
