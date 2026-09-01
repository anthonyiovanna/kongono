<?php

declare(strict_types=1);

namespace Kongono\AgentRuntime\Capability;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Preserves whether the explicit capability reports performance or failure. */
#[Api(ApiStatus::Experimental)]
enum AgentCapabilityOutcome: string
{
    case Performed = 'performed';
    case Failed = 'failed';
}
