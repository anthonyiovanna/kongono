<?php

declare(strict_types=1);

namespace Kongono\ObservabilityApplication;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the closed lifecycle of one application observability operation. */
#[Api(ApiStatus::Experimental)]
enum ApplicationObservabilityOperationState: string
{
    case Active = 'active';
    case Completing = 'completing';
    case Closed = 'closed';
}
