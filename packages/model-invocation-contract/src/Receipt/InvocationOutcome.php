<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Receipt;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Classifies one bounded invocation receipt claim. */
#[Api(ApiStatus::Experimental)]
enum InvocationOutcome: string
{
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Rejected = 'rejected';
    case Uncertain = 'uncertain';
}
