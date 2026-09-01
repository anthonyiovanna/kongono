<?php

declare(strict_types=1);

namespace Kongono\AgentPrincipalContract\Constraint;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Names one inert constraint dimension without evaluating or enforcing it. */
#[Api(ApiStatus::Experimental)]
enum ConstraintKind: string
{
    case StepLimit = 'step_limit';
    case DurationMillisecondsLimit = 'duration_milliseconds_limit';
    case TokenLimit = 'token_limit';
    case CapabilityCallLimit = 'capability_call_limit';
    case RetryLimit = 'retry_limit';
    case DelegationDepthLimit = 'delegation_depth_limit';
    case OutputBytesLimit = 'output_bytes_limit';
    case RetainedStateBytesLimit = 'retained_state_bytes_limit';
    case ConcurrencyLimit = 'concurrency_limit';
}
