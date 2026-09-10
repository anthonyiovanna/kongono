<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceContract\Lifecycle;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Supplies closed lifecycle vocabulary without implementing a state machine. */
#[Api(ApiStatus::Experimental)]
enum ExternalServiceLifecycleState: string
{
    case Prepared = 'prepared';
    case Resolving = 'resolving';
    case Resolved = 'resolved';
    case Leasing = 'leasing';
    case Leased = 'leased';
    case Invoking = 'invoking';
    case Retiring = 'retiring';
    case Retired = 'retired';
    case Failed = 'failed';
}
