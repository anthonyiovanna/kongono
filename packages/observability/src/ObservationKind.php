<?php

declare(strict_types=1);

namespace Kongono\Observability;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Classifies neutral observation meaning without creating a recording pipeline. */
#[Api(ApiStatus::Experimental)]
enum ObservationKind: string
{
    case Trace = 'trace';
    case Metric = 'metric';
    case OperationalEvent = 'operational_event';
    case SecurityAudit = 'security_audit';
}
