<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceContract\Failure;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Classifies one bounded failure without retaining foreign failure content. */
#[Api(ApiStatus::Experimental)]
enum ExternalServiceFailureKind: string
{
    case Binding = 'binding';
    case Resolution = 'resolution';
    case Credential = 'credential';
    case Authority = 'authority';
    case Policy = 'policy';
    case Transport = 'transport';
    case Service = 'service';
    case Cancelled = 'cancelled';
    case Cleanup = 'cleanup';
    case Invariant = 'invariant';
}
