<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Lifecycle;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Supplies lifecycle vocabulary only; it is not a state machine. */
#[Api(ApiStatus::Experimental)]
enum InvocationState: string
{
    case Prepared = 'prepared';
    case Active = 'active';
    case Streaming = 'streaming';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Retired = 'retired';
}
