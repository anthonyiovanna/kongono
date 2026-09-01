<?php

declare(strict_types=1);

namespace Kongono\AgentRuntime\Runtime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Names the closed synchronous lifecycle states for one runtime call. */
#[Api(ApiStatus::Experimental)]
enum AgentRuntimeState: string
{
    case Created = 'created';
    case Proposing = 'proposing';
    case Proposed = 'proposed';
    case Invoking = 'invoking';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Failed = 'failed';
    case Retired = 'retired';
}
