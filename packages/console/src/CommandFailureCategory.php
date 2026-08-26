<?php

declare(strict_types=1);

namespace Kongono\Console;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Classifies bounded failure evidence without retaining raw failure material. */
#[Api(ApiStatus::Experimental)]
enum CommandFailureCategory: string
{
    case InvalidInput = 'invalid_input';
    case Precondition = 'precondition';
    case Denied = 'denied';
    case Capability = 'capability';
    case Infrastructure = 'infrastructure';
    case Cancelled = 'cancelled';
}
