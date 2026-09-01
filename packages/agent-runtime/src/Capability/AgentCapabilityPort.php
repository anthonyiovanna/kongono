<?php

declare(strict_types=1);

namespace Kongono\AgentRuntime\Capability;

use Kongono\AgentPrincipalContract\Capability\CapabilityIdentity;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Exposes one exact operation-scoped capability effect and explicit cleanup. */
#[Api(ApiStatus::Experimental)]
interface AgentCapabilityPort
{
    public function capabilityIdentity(): CapabilityIdentity;

    public function invoke(AgentCapabilityInvocation $invocation): AgentCapabilityResult;

    public function close(): void;
}
