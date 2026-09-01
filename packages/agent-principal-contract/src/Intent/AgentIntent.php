<?php

declare(strict_types=1);

namespace Kongono\AgentPrincipalContract\Intent;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Pairs an identity and purpose as inert requested intent. */
#[Api(ApiStatus::Experimental)]
final readonly class AgentIntent
{
    public function __construct(
        private IntentIdentity $identity,
        private IntentPurpose $purpose,
    ) {}

    public function identity(): IntentIdentity
    {
        return $this->identity;
    }

    public function purpose(): IntentPurpose
    {
        return $this->purpose;
    }
}
