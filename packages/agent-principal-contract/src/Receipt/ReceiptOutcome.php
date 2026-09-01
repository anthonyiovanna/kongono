<?php

declare(strict_types=1);

namespace Kongono\AgentPrincipalContract\Receipt;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Classifies a bounded receipt claim without granting reusable authority. */
#[Api(ApiStatus::Experimental)]
enum ReceiptOutcome: string
{
    case Requested = 'requested';
    case Proposed = 'proposed';
    case Authorized = 'authorized';
    case Attempted = 'attempted';
    case Performed = 'performed';
    case Denied = 'denied';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Retired = 'retired';
}
