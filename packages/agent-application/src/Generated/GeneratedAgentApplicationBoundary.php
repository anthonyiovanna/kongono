<?php

declare(strict_types=1);

namespace Kongono\AgentApplication\Generated;

use Kongono\AgentApplication\Profile\AgentApplicationProfile;
use Kongono\AgentApplication\Runtime\AgentApplication;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Fixed boundary implemented only by exact generated single-profile wiring. */
#[Api(ApiStatus::Experimental)]
interface GeneratedAgentApplicationBoundary
{
    public function start(AgentApplicationProfile $profile): AgentApplication;
}
