<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressContract\Failure;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Classifies a bounded failure claim without retaining foreign detail. */
#[Api(ApiStatus::Experimental)]
enum EgressFailureKind: string
{
    case Validation = 'validation';
    case PolicyDenied = 'policy_denied';
    case Resolution = 'resolution';
    case Connection = 'connection';
    case Tls = 'tls';
    case Protocol = 'protocol';
    case Redirect = 'redirect';
    case Budget = 'budget';
    case Cancellation = 'cancellation';
    case Timeout = 'timeout';
    case Response = 'response';
    case Cleanup = 'cleanup';
    case UncertainEffect = 'uncertain_effect';
}
