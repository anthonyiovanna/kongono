<?php

declare(strict_types=1);

namespace Kongono\AgentPrincipalContract\Capability;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Describes one inert request; it is never a grant, tool, or callable. */
#[Api(ApiStatus::Experimental)]
final readonly class CapabilityRequest
{
    public function __construct(
        private CapabilityIdentity $identity,
        private CapabilityPurpose $purpose,
        private CapabilityRequirement $requirement,
    ) {}

    public function identity(): CapabilityIdentity
    {
        return $this->identity;
    }

    public function purpose(): CapabilityPurpose
    {
        return $this->purpose;
    }

    public function requirement(): CapabilityRequirement
    {
        return $this->requirement;
    }
}
