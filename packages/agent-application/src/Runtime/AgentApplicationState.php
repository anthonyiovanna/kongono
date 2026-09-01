<?php

declare(strict_types=1);

namespace Kongono\AgentApplication\Runtime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Names the only two application lifecycle states. */
#[Api(ApiStatus::Experimental)]
enum AgentApplicationState: string
{
    case Open = 'open';
    case Closed = 'closed';
}
