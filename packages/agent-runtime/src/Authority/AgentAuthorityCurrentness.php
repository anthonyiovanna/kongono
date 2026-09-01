<?php

declare(strict_types=1);

namespace Kongono\AgentRuntime\Authority;

use Kongono\AgentAdmission\Authority\AgentAuthorityPlan;
use Kongono\AgentPrincipalContract\Identity\OperationReference;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Rechecks caller-owned authority evidence without evaluating or granting authority. */
#[Api(ApiStatus::Experimental)]
interface AgentAuthorityCurrentness
{
    public function isCurrent(AgentAuthorityPlan $plan, OperationReference $operation): bool;
}
