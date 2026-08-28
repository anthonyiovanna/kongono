<?php

declare(strict_types=1);

namespace Kongono\OpenTelemetry;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the exact explicit bridge lifecycle. */
#[Api(ApiStatus::Experimental)]
enum OpenTelemetryBridgeState: string
{
    case Open = 'open';
    case Flushing = 'flushing';
    case Closing = 'closing';
    case Closed = 'closed';
}
