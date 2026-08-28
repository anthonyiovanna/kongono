<?php

declare(strict_types=1);

namespace Kongono\Tracing;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the bounded lifecycle state of a trace operation or span. */
#[Api(ApiStatus::Experimental)]
enum TraceLifecycleState: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Retired = 'retired';
}
